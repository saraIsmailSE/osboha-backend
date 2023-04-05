<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PollOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'option',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function votes()
    {
        return $this->hasMany(PollVote::class, 'poll_option_id');
    }
}