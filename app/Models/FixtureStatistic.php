<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixtureStatistic extends Model
{
    protected $fillable = [
        'fixture_id',
        'period',
        'group_name',
        'statistic_name',
        'key',
        'home',
        'away',
        'compare_code',
        'statistics_type',
        'value_type',
        'home_value',
        'away_value',
        'home_total',
        'away_total',
        'render_type'
    ];

    protected $casts = [
        'home_value' => 'float',
        'away_value' => 'float',
        'home_total' => 'integer',
        'away_total' => 'integer',
        'compare_code' => 'integer',
        'render_type' => 'integer'
    ];

    public function fixture(): BelongsTo
    {
        return $this->belongsTo(Fixture::class);
    }
}
