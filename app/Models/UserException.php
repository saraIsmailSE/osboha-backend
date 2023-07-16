<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserException extends Model
{
    use HasFactory;

    protected $fillable=[
        'user_id',
        'week_id',
        'reason',
        'type_id',
        'status',
        'end_at',
        'note',
        'reviewer_id',
        'desired_duration'

    ];

    protected $with = array('type','user','reviewer', 'media');

    public function User()
    {
        return $this->belongsTo(User::class,"user_id");
    }
    public function reviewer()
    {
        return $this->belongsTo(User::class,"reviewer_id");
    }

    public function Week()
    {
        return $this->belongsTo(Week::class);
    }

    public function type()
    {
        return $this->belongsTo(ExceptionType::class,'type_id');
    }
    public function media()
    {
        return $this->hasOne(Media::class, 'user_exception_id');
    }


}