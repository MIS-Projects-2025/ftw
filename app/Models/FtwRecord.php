<?php

namespace App\Models;

use App\Models\FtwApproval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FtwRecord extends Model
{
    // Process status constants
    const PROCESS_STATUS_PENDING_SUP = 1;
    const  PROCESS_STATUS_PENDING_ACK = 2;
    const PROCESS_STATUS_COMPLETED = 3;
    const PROCESS_STATUS_DISAPPROVED = 6;

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
        'process_status',
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

    // -------------------------------------------------------
    // Helper Methods
    // -------------------------------------------------------

    /**
     * Check if the record is pending for a specific user type
     */
    public function isPendingFor(bool $isSupervisor): bool
    {
        if ($isSupervisor) {
            return $this->process_status === self::PROCESS_STATUS_PENDING_SUP;
        }
        return $this->process_status === self::PROCESS_STATUS_PENDING_ACK;
    }

    /**
     * Check if the record is completed
     */
    public function isCompleted(): bool
    {
        return $this->process_status === self::PROCESS_STATUS_COMPLETED;
    }

    /**
     * Get process status label
     */
    public function getProcessStatusLabelAttribute(): string
    {
        return match ($this->process_status) {

            self::PROCESS_STATUS_PENDING_SUP => 'Pending Supervisor Approval',
            self::PROCESS_STATUS_PENDING_ACK => 'Pending Employee Acknowledgement',
            self::PROCESS_STATUS_COMPLETED => 'Completed',
            default => 'Unknown'
        };
    }

    /**
     * Get process status badge class
     */
    public function getProcessStatusBadgeAttribute(): string
    {
        return match ($this->process_status) {

            self::PROCESS_STATUS_PENDING_SUP => 'warning',
            self::PROCESS_STATUS_PENDING_ACK => 'info',
            self::PROCESS_STATUS_COMPLETED => 'success',
            default => 'dark'
        };
    }
}
