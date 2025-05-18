<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FixturePrediction extends Model
{
    protected $fillable = [
        'fixture_id',
        'win_probability',
        'predicted_score',
        'key_factors',
        'confidence_level',
        'raw_response',
        'analysis_data'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'win_probability' => 'array',
        'predicted_score' => 'array',
        'key_factors' => 'array',
        'analysis_data' => 'array'
    ];

    /**
     * Get the fixture that owns the prediction.
     */
    public function fixture()
    {
        return $this->belongsTo(Fixture::class);
    }
}
