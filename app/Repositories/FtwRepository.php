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
