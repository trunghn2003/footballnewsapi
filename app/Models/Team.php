<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $fillable = [
        'id', 'area_id', 'name', 'short_name', 'tla', 'crest',
        'address', 'website', 'founded', 'club_colors', 'venue', 'last_updated'
    ];

    public $incrementing = false;

    protected $casts = [
        'founded' => 'integer',
        'last_updated' => 'datetime'
    ];

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function coach()
    {
        return $this->hasOne(Coach::class);
    }

    public function homeFixtures()
    {
        return $this->hasMany(Fixture::class, 'home_team_id');
    }

    public function awayFixtures()
    {
        return $this->hasMany(Fixture::class, 'away_team_id');
    }



    public function competitions()
    {
        return $this->belongsToMany(Competition::class, 'team_competition_season')
                    ->withPivot('season_id')
                    ->withTimestamps();
    }

    public function getAllFixtures()
    {
        return Fixture::where('home_team_id', $this->id)
            ->orWhere('away_team_id', $this->id);
    }

    public function players()
    {
        return $this->belongsToMany(Person::class, 'person_team')
                    ->withTimestamps();
                    // ->where('role', 'PLAYER');
    }
}
