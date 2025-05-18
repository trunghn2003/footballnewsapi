<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Standing extends Model
{
    protected $fillable = [
        'competition_id',
        'season_id',
        'matchday',
        'stage',
        'type',
        'group',
        'team_id',
        'position',
        'played_games',
        'form',
        'won',
        'draw',
        'lost',
        'points',
        'goals_for',
        'goals_against',
        'goal_difference'
    ];

    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
