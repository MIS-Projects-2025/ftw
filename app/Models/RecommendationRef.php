<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecommendationRef extends Model
{
    protected $table = 'recommendation_ref';

    protected $primaryKey = 'rec_id';

    public $timestamps = false;

    protected $fillable = [
        'rec_label',
    ];

    // -------------------------------------------------------
    // Relationships
    // -------------------------------------------------------

    /**
     * FTW records that use this recommendation.
     */
    public function ftwRecords(): HasMany
    {
        return $this->hasMany(FtwRecord::class, 'recommendation', 'rec_id');
    }
}
