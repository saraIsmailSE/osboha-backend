<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionAssignee extends Model
{
    use HasFactory;

    protected $table = "questions_assignees";

    protected $fillable = [
        "assigned_by",
        "question_id",
        "assignee_id",
        "is_active"
    ];

    public function assignedBy()
    {
        return $this->belongsTo(User::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, "assignee_id");
    }
}
