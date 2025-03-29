<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lineup extends Model
{
    use HasFactory;

    protected $fillable = [
        'fixture_id',
        'team_id',
        'coach_id',
        'formation',
    ];

    public function fixture()
    {
        return $this->belongsTo(Fixture::class, 'fixture_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function coach()
    {
        return $this->belongsTo(Person::class, 'coach_id');
    }

    public function players()
    {
        return $this->belongsToMany(Person::class, 'lineup_players', 'lineup_id', 'player_id')
                    ->withPivot('position', 'shirt_number', 'grid_position', 'is_substitute');
    }

    public function lineupPlayers()
    {
        return $this->hasMany(LineupPlayer::class, 'lineup_id');
    }
}
