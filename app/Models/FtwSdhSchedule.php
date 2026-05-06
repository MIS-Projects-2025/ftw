<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FtwSdhSchedule extends Model
{
    protected $table = 'ftw_sdh_schedule';

    protected $primaryKey = 'sdh_id';

    public $timestamps = false;

    protected $fillable = [
        'tbl_id',
        'sdh_date',
        'sdh_time',
    ];

    protected $casts = [
        'sdh_date' => 'datetime',
        'sdh_time' => 'string',   // TIME — kept as HH:MM:SS string
    ];

    // -------------------------------------------------------
    // Relationships
    // -------------------------------------------------------

    /**
     * The FTW record this SDH schedule belongs to.
     */
    public function ftwRecord(): BelongsTo
    {
        return $this->belongsTo(FtwRecord::class, 'tbl_id', 'tbl_id');
    }
}
