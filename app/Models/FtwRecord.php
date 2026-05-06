<?php

namespace App\Models;

use App\Models\FtwApproval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FtwRecord extends Model
{
    protected $table = 'ftw_records';

    protected $primaryKey = 'tbl_id';

    public $timestamps = false;

    protected $fillable = [
        'emp_no',
        'emp_name',
        'emp_dept',
        'emp_team',
        'emp_time_in',
        'emp_time_out',
        'emp_diagnose',
        'remarks',
        'recommendation',
        'process_status',
        'ftw_file_link',
        'date_created',
        'duty_nurse',
        'first_aider_name',
        'training_dept',
        'emp_shift',
        'absent_count',
        'disapprove_remarks',
        'ftw_date',
        'process_status', // 1 = Pending, 2 = For Supervisor Approval, 3 = Approved, 4 = Disapproved
    ];

    protected $casts = [
        'emp_time_in'    => 'string',   // TIME columns — keep as string (HH:MM:SS)
        'emp_time_out'   => 'string',
        'emp_diagnose'   => 'string',
        'ftw_file_link'  => 'string',
        'date_created'   => 'datetime',
        'ftw_date'       => 'date',
        'emp_shift'      => 'integer',
        'absent_count'   => 'integer',
        'training_dept'  => 'integer',
        'recommendation' => 'integer',
    ];

    // -------------------------------------------------------
    // Relationships
    // -------------------------------------------------------


    /**
     * The recommendation type for this record.
     */
    public function recommendationRef(): BelongsTo
    {
        return $this->belongsTo(RecommendationRef::class, 'recommendation', 'rec_id');
    }


    /**
     * All absence dates linked to this record.
     */
    public function absenceDates(): HasMany
    {
        return $this->hasMany(FtwAbsenceDate::class, 'tbl_id', 'tbl_id');
    }

    /**
     * The rest schedule entry for this record.
     */
    public function restSchedule(): HasOne
    {
        return $this->hasOne(FtwRestSchedule::class, 'tbl_id', 'tbl_id');
    }

    /**
     * The SDH schedule entry for this record.
     */
    public function sdhSchedule(): HasOne
    {
        return $this->hasOne(FtwSdhSchedule::class, 'tbl_id', 'tbl_id');
    }

    /**
     * All approval steps for this record.
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(FtwApproval::class, 'tbl_id', 'tbl_id');
    }

    // -------------------------------------------------------
    // Scopes
    // -------------------------------------------------------

    /**
     * Filter by recommendation type.
     * Usage: FtwRecord::ofRecommendation(1)->get()
     */
    public function scopeOfRecommendation($query, int $recId)
    {
        return $query->where('recommendation', $recId);
    }

    /**
     * Filter by process status.
     */
    public function scopeOfStatus($query, int $status)
    {
        return $query->where('process_status', $status);
    }

    /**
     * Filter records for a specific employee.
     */
    public function scopeForEmployee($query, int $empNo)
    {
        return $query->where('emp_no', $empNo);
    }

    /**
     * Filter records within a date range (ftw_date).
     */
    public function scopeBetweenDates($query, string $from, string $to)
    {
        return $query->whereBetween('ftw_date', [$from, $to]);
    }
}
