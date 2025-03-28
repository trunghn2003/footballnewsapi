<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Goal extends Model
{
    protected $fillable = [
        'match_id', 'minute', 'extra_time', 'team_id',
        'scorer_id', 'assist_id', 'type'
    ];

    public function fixture()
    {
        return $this->belongsTo(Fixture::class);
    }

    public function scorer()
    {
        return $this->belongsTo(Player::class, 'scorer_id');
    }
}
