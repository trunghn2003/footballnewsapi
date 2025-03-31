<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Season extends Model
{
    protected $fillable = [
        'id',
        'competition_id',
        'start_date',
        'end_date',
        'current_matchday',
        'winner_team_id',
        'name'
    ];

    public $incrementing = false;

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date'
    ];

    protected $dates = ['start_date', 'end_date'];

    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    public function winner()
    {
        return $this->belongsTo(Team::class, 'winner_team_id');
    }

    public function fixtures()
    {
        return $this->hasMany(Fixture::class);
    }
    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_competition_season')
            ->withPivot('competition_id')
            ->withTimestamps();
    }

    // public function stages()
    // {
    //     return $this->hasMany(Stage::class);
    // }
}
