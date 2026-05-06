<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FtwRestSchedule extends Model
{
    protected $table = 'ftw_rest_schedule';

    protected $primaryKey = 'rest_id';

    public $timestamps = false;

    protected $fillable = [
        'tbl_id',
        'rest_date',
        'rest_time_in',
        'rest_time_out',
    ];

    protected $casts = [
        'rest_date'     => 'datetime',
        'rest_time_in'  => 'string',   // TIME — kept as HH:MM:SS string
        'rest_time_out' => 'string',
    ];

    // -------------------------------------------------------
    // Relationships
    // -------------------------------------------------------

    /**
     * The FTW record this rest schedule belongs to.
     */
    public function ftwRecord(): BelongsTo
    {
        return $this->belongsTo(FtwRecord::class, 'tbl_id', 'tbl_id');
    }

    // -------------------------------------------------------
    // Accessors
    // -------------------------------------------------------

    /**
     * Compute rest duration in minutes between time_in and time_out.
     * Returns null if either value is missing.
     */
    public function getDurationMinutesAttribute(): ?int
    {
        if (! $this->rest_time_in || ! $this->rest_time_out) {
            return null;
        }

        [$inH, $inM]   = array_map('intval', explode(':', $this->rest_time_in));
        [$outH, $outM] = array_map('intval', explode(':', $this->rest_time_out));

        return ($outH * 60 + $outM) - ($inH * 60 + $inM);
    }
}
