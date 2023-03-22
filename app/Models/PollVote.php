<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PollVote extends Model
{
    use HasFactory;

    protected $fillable = [
        'poll_option_id',
        'user_id',
        'vote_count',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pollOption()
    {
        return $this->belongsTo(PollOption::class, 'poll_option_id');
    }
}