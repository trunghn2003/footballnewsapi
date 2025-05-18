<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    protected $table = 'persons';
    protected $fillable = [
        'id', 'team_id', 'name', 'first_name', 'last_name',
        'date_of_birth', 'nationality', 'position',
        'shirt_number', 'market_value', 'last_updated'
    ];

    public $incrementing = false;

    protected $casts = [
        'date_of_birth' => 'date',
        'last_updated' => 'datetime'
    ];

    protected $dates = ['date_of_birth'];

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'person_team');
    }

    public function goals()
    {
        return $this->hasMany(Goal::class);
    }
}
