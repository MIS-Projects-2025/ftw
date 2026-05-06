<?php

namespace App\Repositories;

use App\Models\FtwAbsenceDate;
use App\Models\FtwApproval;
use App\Models\FtwRecord;
use App\Models\FtwRestSchedule;
use App\Models\FtwSdhSchedule;
use App\Models\RecommendationRef;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class FtwRepository
{
    private const ALLOWED_ORDER_BY = [
        'date_created',
        'emp_name',
        'emp_dept',
        'recommendation',
        'process_status',
    ];


    public function getHistory(
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
        $orderBy  = in_array($orderBy, self::ALLOWED_ORDER_BY) ? $orderBy : 'date_created';
        $orderDir = strtolower($orderDir) === 'asc' ? 'asc' : 'desc';

        $query = FtwRecord::with([
            'recommendationRef',
            'absenceDates',
            'sdhSchedule',
            'restSchedule',
        ]);

        if (! $isClinic) {
            if ($isSupervisor && ! empty($directReportIds)) {
                $query->whereIn('emp_no', array_unique(array_merge($directReportIds, [$empId])));
            } else {
                $query->where('emp_no', $empId);
            }
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('emp_name', 'like', "%{$search}%")
                    ->orWhere('emp_dept', 'like', "%{$search}%");
            });
        }

        $paginator = $query->orderBy($orderBy, $orderDir)->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => collect($paginator->items())->map(fn($r) => $this->formatRecord($r))->values()->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem() ?? 0,
                'to'           => $paginator->lastItem() ?? 0,
            ],
        ];
    }


    public function getPending(
        int $empId,
        bool $isSupervisor,
        string $search,
        string $orderBy,
        string $orderDir,
        int $page,
        int $perPage,
    ): array {
        $orderBy  = in_array($orderBy, self::ALLOWED_ORDER_BY) ? $orderBy : 'date_created';
        $orderDir = strtolower($orderDir) === 'asc' ? 'asc' : 'desc';
        $role     = $isSupervisor ? FtwApproval::ROLE_IMMEDIATE_SUP : FtwApproval::ROLE_ACK_BY;

        $query = FtwRecord::with([
            'recommendationRef',
            'absenceDates',
            'sdhSchedule',
            'restSchedule',
        ])
            ->whereHas('approvals', function ($q) use ($empId, $role) {
                $q->where('approver_emp', $empId)
                    ->where('role', $role)
                    ->where('status', FtwApproval::STATUS_PENDING);
            });

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('emp_name', 'like', "%{$search}%")
                    ->orWhere('emp_dept', 'like', "%{$search}%");
            });
        }

        $paginator = $query->orderBy($orderBy, $orderDir)->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => collect($paginator->items())->map(fn($r) => $this->formatRecord($r))->values()->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem() ?? 0,
                'to'           => $paginator->lastItem() ?? 0,
            ],
        ];
    }


    private function formatRecord(FtwRecord $r): array
    {
        $shiftMap = ['1' => 'Day Shift', '2' => 'Night Shift', '3' => 'Normal'];

        return [
            // ── Core ──────────────────────────────────────────────────────────
            'tbl_id'          => $r->tbl_id,
            'emp_no'          => $r->emp_no,
            'emp_name'        => $r->emp_name  ?? '—',
            'emp_dept'        => $r->emp_dept  ?? '—',
            'emp_team'        => $r->emp_team  ?? '—',
            'recommendation'  => $r->recommendation,
            'rec_label'       => $r->recommendationRef?->rec_label ?? '—',
            'process_status'  => $r->process_status,
            'date_created'    => $r->date_created?->format('m-d-Y') ?? '—',

            // ── Shift (used by Fit to Work, Sent Home, Hospital, Unfit) ───────
            'emp_shift'       => $r->emp_shift,
            'emp_shift_label' => $shiftMap[$r->emp_shift] ?? null,

            // ── Fit to Work ───────────────────────────────────────────────────
            'emp_time_in'     => $r->emp_time_in
                ? \Carbon\Carbon::parse($r->emp_time_in)->format('h:i A')
                : null,
            'emp_diagnose'    => $r->emp_diagnose,
            'absent_count'    => $r->absent_count ?? 0,
            'absence_dates'   => $r->absenceDates
                ?->sortBy('absence_date')
                ->map(fn($d) => \Carbon\Carbon::parse($d->absence_date)->format('M d, Y'))
                ->values()
                ->all()
                ?? [],
            'ftw_file'        => $r->ftw_file_link ? basename($r->ftw_file_link) : null,
            'ftw_file_url'    => $r->ftw_file_link ? asset($r->ftw_file_link)   : null,

            // ── Sent Home / Hospital / Unfit ──────────────────────────────────
            'sdh_date'        => $r->sdhSchedule?->sdh_date
                ? \Carbon\Carbon::parse($r->sdhSchedule->sdh_date)->format('M d, Y')
                : null,
            'sdh_time'        => $r->sdhSchedule?->sdh_time
                ? \Carbon\Carbon::parse($r->sdhSchedule->sdh_time)->format('h:i A')
                : null,

            // ── Unfit to Work ─────────────────────────────────────────────────
            'remarks'         => $r->remarks,

            // ── Rest ──────────────────────────────────────────────────────────
            'rest_date'       => $r->restSchedule?->rest_date
                ? \Carbon\Carbon::parse($r->restSchedule->rest_date)->format('M d, Y')
                : null,
            'rest_time_in'    => $r->restSchedule?->rest_time_in
                ? \Carbon\Carbon::parse($r->restSchedule->rest_time_in)->format('h:i A')
                : null,
        ];
    }

    public function getRecommendations(): Collection
    {
        // Exclude rec 6 (Return to work area) — handled as a table action, not a creation option
        return RecommendationRef::where('rec_id', '!=', 6)->orderBy('rec_id')->get();
    }

    public function create(array $data): FtwRecord
    {
        return FtwRecord::create($data);
    }

    public function saveAbsenceDates(int $tblId, array $dates): void
    {
        foreach ($dates as $date) {
            FtwAbsenceDate::create([
                'tbl_id'       => $tblId,
                'absence_date' => $date,
            ]);
        }
    }

    public function saveRestSchedule(int $tblId, string $restTimeIn): FtwRestSchedule
    {
        return FtwRestSchedule::create([
            'tbl_id'       => $tblId,
            'rest_date'    => now()->toDateString(),
            'rest_time_in' => $restTimeIn,
        ]);
    }

    public function updateRestTimeOut(int $tblId, string $restTimeOut): void
    {
        FtwRestSchedule::where('tbl_id', $tblId)->update(['rest_time_out' => $restTimeOut]);
    }

    public function saveSdhSchedule(int $tblId, string $date, ?string $sdhTime = null): FtwSdhSchedule
    {
        return FtwSdhSchedule::create([
            'tbl_id'   => $tblId,
            'sdh_date' => $date,
            'sdh_time' => $sdhTime,
        ]);
    }

    public function createApproval(int $tblId, int $approverEmp, string $role, int $status = 0, ?string $remarks = null)
    {
        return FtwApproval::create([
            'tbl_id'       => $tblId,
            'approver_emp' => $approverEmp,
            'role'         => $role,
            'status'       => $status,
            'action_date'  => $status !== FtwApproval::STATUS_PENDING ? now() : null,
            'remarks'      => $remarks,
        ]);
    }
}
