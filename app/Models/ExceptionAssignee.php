<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExceptionAssignee extends Model
{
    use HasFactory;

    protected $table = "exceptions_assignees";

    protected $fillable = [
        "assigned_by",
        "exception_id",
        "assignee_id",
        "is_active"
    ];

    protected $with = array('assignee.roles');

    public function assignedBy()
    {
        return $this->belongsTo(User::class);
    }

    public function exceptions()
    {
        return $this->belongsTo(UserException::class, 'exception_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, "assignee_id");
    }
}
