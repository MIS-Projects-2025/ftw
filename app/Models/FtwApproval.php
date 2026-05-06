<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FtwApproval extends Model
{
    use HasFactory;

    protected $table = 'ftw_approvals';

    protected $primaryKey = 'approval_id';

    public $timestamps = false;

    protected $fillable = [
        'tbl_id',
        'approver_emp',
        'role',
        'status',
        'action_date',
        'remarks'
    ];

    protected $casts = [
        'tbl_id' => 'integer',
        'approver_emp' => 'integer',
        'status' => 'integer',
        'action_date' => 'datetime',
        'remarks' => 'string'
    ];

    // Status constants
    const STATUS_PENDING = 0;
    const STATUS_APPROVED = 1;
    const STATUS_REJECTED = 2;

    // Role constants
    const ROLE_IMMEDIATE_SUP = 'immediate_sup';
    const ROLE_ACK_BY = 'ack_by';

    /**
     * Get the FTW record that owns this approval.
     */
    public function ftwRecord()
    {
        return $this->belongsTo(FtwRecord::class, 'tbl_id', 'tbl_id');
    }


    /**
     * Scope for pending approvals.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for approved approvals.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope for rejected approvals.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Scope for immediate supervisor role.
     */
    public function scopeImmediateSup($query)
    {
        return $query->where('role', self::ROLE_IMMEDIATE_SUP);
    }

    /**
     * Scope for ack_by role.
     */
    public function scopeAckBy($query)
    {
        return $query->where('role', self::ROLE_ACK_BY);
    }

    /**
     * Check if approval is pending.
     */
    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if approval is approved.
     */
    public function isApproved()
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if approval is rejected.
     */
    public function isRejected()
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Check if role is immediate supervisor.
     */
    public function isImmediateSup()
    {
        return $this->role === self::ROLE_IMMEDIATE_SUP;
    }

    /**
     * Check if role is ack_by.
     */
    public function isAckBy()
    {
        return $this->role === self::ROLE_ACK_BY;
    }

    /**
     * Approve the approval.
     */
    public function approve($remarks = null)
    {
        $this->status = self::STATUS_APPROVED;
        $this->action_date = now();
        if ($remarks) {
            $this->remarks = $remarks;
        }
        return $this->save();
    }

    /**
     * Reject the approval.
     */
    public function reject($remarks = null)
    {
        $this->status = self::STATUS_REJECTED;
        $this->action_date = now();
        if ($remarks) {
            $this->remarks = $remarks;
        }
        return $this->save();
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            default => 'Unknown'
        };
    }

    /**
     * Get role label.
     */
    public function getRoleLabelAttribute()
    {
        return match ($this->role) {
            self::ROLE_IMMEDIATE_SUP => 'Immediate Supervisor',
            self::ROLE_ACK_BY => 'Acknowledged By',
            default => 'Unknown'
        };
    }

    /**
     * Get status badge class for Bootstrap/UI.
     */
    public function getStatusBadgeAttribute()
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger',
            default => 'secondary'
        };
    }
}
