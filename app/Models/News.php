<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'source',
        'published_at',
        'competition_id'
    ];

    protected $dates = ['published_at', 'deleted_at'];


    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'news_teams');
    }

    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    public function savedByUsers()
    {
        return $this->belongsToMany(User::class, 'saved_news')
                    ->withTimestamps();
    }
}
