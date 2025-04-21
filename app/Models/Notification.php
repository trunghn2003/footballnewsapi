<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    protected $casts = [
        'data' => 'array',
        'scheduled_at' => 'datetime',
        'read_at' => 'datetime',
    ];
    protected $fillable = [
        'user_id',
        'notifiable_type',
        'notifiable_id',
        'type',
        'data',
        'read_at',
        'scheduled_at',
        'message',
        'title',
    ];


    public function notifiable()
    {
        return $this->morphTo();
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
