<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FtwAbsenceDate extends Model
{
    protected $table = 'ftw_absence_dates';

    protected $primaryKey = 'absence_id';

    public $timestamps = false;

    protected $fillable = [
        'tbl_id',
        'absence_date',
    ];

    protected $casts = [
        'absence_date' => 'date',
    ];

    // -------------------------------------------------------
    // Relationships
    // -------------------------------------------------------

    /**
     * The FTW record this absence date belongs to.
     */
    public function ftwRecord(): BelongsTo
    {
        return $this->belongsTo(FtwRecord::class, 'tbl_id', 'tbl_id');
    }

    // -------------------------------------------------------
    // Scopes
    // -------------------------------------------------------

    /**
     * Filter absence dates within a range.
     * Usage: FtwAbsenceDate::inRange('2025-01-01', '2025-03-31')->get()
     */
    public function scopeInRange($query, string $from, string $to)
    {
        return $query->whereBetween('absence_date', [$from, $to]);
    }
}
