<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        "question",
        "status",
        "user_id",
        "assignee_id"

    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, "assignee_id");
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }
}
