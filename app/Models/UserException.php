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
    ];

    public function User()
    {
        return $this->belongsTo(User::class);
    }

    public function Week()
    {
        return $this->belongsTo(Week::class);
    }

    public function Type()
    {
        return $this->belongsTo(ExceptionType::class,'type_id');
    }

}