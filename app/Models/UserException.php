<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserException extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'requested_by',
        'week_id',
        'reason',
        'type_id',
        'status',
        'end_at',
        'note',
        'reviewer_id',
        'desired_duration'

    ];

    protected $with = array('type', 'user', 'reviewer', 'media');
    protected $appends = ['current_assignee'];


    public function User()
    {
        return $this->belongsTo(User::class, "user_id");
    }
    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
    public function reviewer()
    {
        return $this->belongsTo(User::class, "reviewer_id");
    }
    public function Week()
    {
        return $this->belongsTo(Week::class);
    }

    public function type()
    {
        return $this->belongsTo(ExceptionType::class, 'type_id');
    }
    public function media()
    {
        return $this->hasOne(Media::class, 'user_exception_id');
    }
    public function assignees()
    {
        return $this->hasMany(ExceptionAssignee::class, 'exception_id');
    }
    public function getCurrentAssigneeAttribute()
    {
        return $this->assignees->where('is_active', 1)->first();
    }
    public function notes()
    {
        return $this->hasMany(UserExceptionNote::class, 'user_exception_id');
    }
}
