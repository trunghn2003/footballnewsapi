<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bet extends Model
{
    protected $fillable = [
        'user_id',
        'fixture_id',
        'bet_type', // 'WIN', 'DRAW', 'LOSS', 'SCORE'
        'predicted_score',
        'amount',
        'odds',
        'potential_win',
        'status', // 'PENDING', 'WON', 'LOST'
        'result'
    ];

    protected $casts = [
        'amount' => 'float',
        'odds' => 'float',
        'potential_win' => 'float',
        'predicted_score' => 'array'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fixture(): BelongsTo
    {
        return $this->belongsTo(Fixture::class);
    }
}
