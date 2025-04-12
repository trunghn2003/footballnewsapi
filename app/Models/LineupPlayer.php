<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LineupPlayer extends Model
{
    use HasFactory;

    protected $table = 'lineup_players';

    protected $fillable = [
        'lineup_id',
        'player_id',
        'position',
        'shirt_number',
        'grid_position',
        'is_substitute',
        'statistics'
    ];
    protected $casts = [
        'statistics' => 'array',
    ];

    public function lineup()
    {
        return $this->belongsTo(Lineup::class, 'lineup_id');
    }

    public function player()
    {
        return $this->belongsTo(Person::class, 'player_id');
    }
};
