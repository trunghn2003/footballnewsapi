<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Competition extends Model
{
    protected $fillable = [
        'id',
        'area_id',
        'name',
        'code',
        'type',
        'emblem',
        'plan',
        'current_season_id',
        'number_of_available_seasons',
        'last_updated'
    ];

    public $incrementing = false;

    protected $casts = [
        'last_updated' => 'datetime'
    ];

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function currentSeason()
    {
        return $this->hasOne(Season::class, 'competition_id')
            ->where(function ($query) {
                $query->whereRaw('YEAR(start_date) = 2024');
            })
            ->orderByDesc('start_date')
            ->latest();
    }



    public function seasons()
    {
        return $this->hasMany(Season::class);
    }

    public function fixtures()
    {
        return $this->hasMany(Fixture::class);
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'competition_team');
    }
}
