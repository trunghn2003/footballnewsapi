<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PinnedFixture extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'fixture_id',
        'notify_before',
        'notify_result'
    ];

    protected $casts = [
        'notify_before' => 'boolean',
        'notify_result' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function fixture()
    {
        return $this->belongsTo(Fixture::class);
    }
}
