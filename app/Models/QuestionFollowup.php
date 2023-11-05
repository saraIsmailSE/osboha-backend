<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionFollowup extends Model
{
    use HasFactory;

    protected $table = 'questions_followup';

    protected $fillable = [
        'user_id',
        'week_id',
        'counter',
        'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
