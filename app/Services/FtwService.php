<?php

namespace App\Services;

use App\Models\FtwApproval;
use App\Models\FtwRecord;
use App\Repositories\FtwRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class FtwService
{
    // Recommendations that require absence dates + shift
    const REC_NEEDS_ABSENCE = [1, 5];

    // Recommendations that require SDH date (Sent Home, Hospital, Unfit to Work)
    const REC_NEEDS_SDH = [2, 3, 5];

    // Recommendations that require rest time in
    const REC_NEEDS_REST = [4];

    public function __construct(
        protected FtwRepository $repo,
        protected HrisApiService $hris,
    ) {}



    public function getHistoryData(
        int $empId,
        bool $isClinic,
        bool $isSupervisor,
        array $directReportIds,
        string $search,
        string $orderBy,
        string $orderDir,
        int $page,
        int $perPage,
    ): array {
        return $this->repo->getHistory(
            $empId,
            $isClinic,
            $isSupervisor,
            $directReportIds,
            $search,
            $orderBy,
            $orderDir,
            $page,
            $perPage,
        );
    }


    public function getPendingData(
        int $empId,
        bool $isClinic,
        bool $isSupervisor,
        string $search,
        string $orderBy,
        string $orderDir,
        int $page,
        int $perPage,
    ): array {
        if ($isClinic) {
            return [
                'data' => [],
                'meta' => ['current_page' => 1, 'last_page' => 1, 'total' => 0, 'from' => 0, 'to' => 0],
            ];
        }

        return $this->repo->getPending($empId, $isSupervisor, $search, $orderBy, $orderDir, $page, $perPage);
    }


    public function handleAction(int $tblId, int $empId, string $action, ?string $remarks): void
    {
        DB::transaction(function () use ($tblId, $empId, $action, $remarks) {
            $record = FtwRecord::findOrFail($tblId);

            [$role, $newApprovalStatus, $newProcessStatus] = match ($action) {
                'approve'     => [
                    FtwApproval::ROLE_IMMEDIATE_SUP,
                    FtwApproval::STATUS_APPROVED,
                    FtwRecord::PROCESS_STATUS_PENDING_ACK  // Move to pending employee acknowledgement
                ],
                'disapprove'  => [
                    FtwApproval::ROLE_IMMEDIATE_SUP,
                    FtwApproval::STATUS_REJECTED,
                    FtwRecord::PROCESS_STATUS_COMPLETED    // Mark as completed (rejected)
                ],
                'acknowledge' => [
                    FtwApproval::ROLE_ACK_BY,
                    FtwApproval::STATUS_APPROVED,
                    FtwRecord::PROCESS_STATUS_COMPLETED    // Mark as completed (fully approved)
                ],
                'reject'      => [
                    FtwApproval::ROLE_ACK_BY,
                    FtwApproval::STATUS_REJECTED,
                    FtwRecord::PROCESS_STATUS_COMPLETED    // Mark as completed (rejected)
                ],
            };

            $approval = FtwApproval::where('tbl_id', $tblId)
                ->where('approver_emp', $empId)
                ->where('role', $role)
                ->where('status', FtwApproval::STATUS_PENDING)
                ->firstOrFail();

            $approval->update([
                'status'      => $newApprovalStatus,
                'action_date' => now(),
                'remarks'     => $remarks,
            ]);

            $record->update(['process_status' => $newProcessStatus]);
        });
    }


    public function getFormData(): array
    {
        return [
            'recommendations' => $this->repo->getRecommendations(),
        ];
    }

    public function store(array $data, array $empData): FtwRecord
    {
        $rec      = (int) $data['recommendation'];
        $empId    = (int) ($empData['emp_id'] ?? 0);
        $isClinic = (int) ($empData['emp_station_id'] ?? 0) === 39;

        $isSupervisor = ! $isClinic && $empId > 0
            && count($this->hris->fetchDirectReports($empId)) > 0;

        // Use constants instead of magic numbers
        $processStatus = ($isClinic || ! $isSupervisor)
            ? FtwRecord::PROCESS_STATUS_DRAFT
            : FtwRecord::PROCESS_STATUS_PENDING_SUP;

        $dutyNurse = $empId;

        // Resolve the immediate supervisor before the transaction (external API call)
        if ($isClinic) {
            $approvers    = $this->hris->fetchApprovers((int) $data['emp_no']);
            $immediateSup = $approvers ? ($approvers['approver1_id'] ?? 0) : 0;
        } else {
            $immediateSup = $isSupervisor ? $empId : 0;
        }

        return DB::transaction(function () use ($data, $rec, $processStatus, $dutyNurse, $immediateSup) {

            // ── 1. Main record ──────────────────────────────────────────────
            $record = $this->repo->create([
                'emp_no'         => $data['emp_no'],
                'emp_name'       => $data['emp_name']  ?? null,
                'emp_dept'       => $data['emp_dept']  ?? null,
                'emp_team'       => $data['emp_team']  ?? null,
                'emp_time_in'    => ! in_array($rec, [4, 5]) ? ($data['emp_time_in'] ?? null) : null,
                'emp_diagnose'   => $rec !== 5 ? ($data['emp_diagnose'] ?? null) : null,
                'remarks'        => $rec === 5 ? ($data['remarks'] ?? null) : null,
                'recommendation' => $rec,
                'process_status' => $processStatus,
                'date_created'   => now(),
                'duty_nurse'     => $dutyNurse,
                'ftw_file_link'  => $data['ftw_file_link'] ?? null,
                'emp_shift'      => in_array($rec, array_merge(self::REC_NEEDS_ABSENCE, self::REC_NEEDS_SDH))
                    ? ($data['emp_shift'] ?? null) : null,
                'absent_count'   => in_array($rec, self::REC_NEEDS_ABSENCE) ? ($data['absent_count'] ?? 0) : 0,
            ]);

            // ── 2. Absence dates (Fit to Work / Unfit) ──────────────────────
            if (in_array($rec, self::REC_NEEDS_ABSENCE) && ! empty($data['absence_dates'])) {
                $this->repo->saveAbsenceDates($record->tbl_id, $data['absence_dates']);
            }

            // ── 3. SDH schedule (Sent Home / Hospital / Unfit) ─────────────
            if (in_array($rec, self::REC_NEEDS_SDH) && ! empty($data['sdh_date'])) {
                $this->repo->saveSdhSchedule($record->tbl_id, $data['sdh_date'], $data['sdh_time'] ?? null);
            }

            // ── 4. Rest schedule ────────────────────────────────────────────
            if (in_array($rec, self::REC_NEEDS_REST) && ! empty($data['rest_time_in'])) {
                $this->repo->saveRestSchedule($record->tbl_id, $data['rest_time_in']);
            }

            // ── 5. Approvals ────────────────────────────────────────────────
            if ($processStatus === 1 && $immediateSup > 0) {
                $this->repo->createApproval(
                    $record->tbl_id,
                    $immediateSup,
                    FtwApproval::ROLE_IMMEDIATE_SUP,
                    FtwApproval::STATUS_PENDING,
                );
            }

            if ($processStatus === 2) {
                if ($immediateSup > 0) {
                    $this->repo->createApproval(
                        $record->tbl_id,
                        $immediateSup,
                        FtwApproval::ROLE_IMMEDIATE_SUP,
                        FtwApproval::STATUS_APPROVED,
                    );
                }

                if ((int) $record->emp_no > 0) {
                    $this->repo->createApproval(
                        $record->tbl_id,
                        (int) $record->emp_no,
                        FtwApproval::ROLE_ACK_BY,
                        FtwApproval::STATUS_PENDING,
                    );
                }
            }

            return $record;
        });
    }
}
