<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    protected $fillable = [
        'id', 'name', 'code', 'flag'
    ];

    public $incrementing = false;

    public function competitions()
    {
        return $this->hasMany(Competition::class);
    }

    public function teams()
    {
        return $this->hasMany(Team::class);
    }
}
