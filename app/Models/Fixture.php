<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fixture extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'competition_id',
        'season_id',
        'utc_date',
        'status',
        'matchday',
        'stage',
        'group',
        'last_updated',
        'home_team_id',
        'away_team_id',
        'winner',
        'duration',
        'full_time_home_score',
        'full_time_away_score',
        'half_time_home_score',
        'half_time_away_score',
        'extra_time_home_score',
        'extra_time_away_score',
        'penalties_home_score',
        'penalties_away_score',
        'venue',
        'referee_id'
    ];

    public $incrementing = false;

    protected $casts = [
        'utc_date' => 'datetime',
        'last_updated' => 'datetime'
    ];

    protected $dates = ['utc_date'];

    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function homeTeam()
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam()
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function goals()
    {
        return $this->hasMany(Goal::class, 'fixture_id');
    }
    public function lineups()
    {
        return $this->hasMany(Lineup::class, 'fixture_id');
    }

    public function homeLineup()
    {
        return $this->hasOne(Lineup::class, 'fixture_id')

            ->where('team_id', $this->home_team_id);
    }

    public function awayLineup()
    {
        return $this->hasOne(Lineup::class, 'fixture_id')

            ->where('team_id', $this->away_team_id);
    }
}
