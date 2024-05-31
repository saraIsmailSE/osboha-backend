<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserExceptionNote extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_exception_id',
        'from_id',
        'body',
        'status',
    ];
    public function exception()
    {
        return $this->belongsTo(UserException::class, 'user_exception_id');
    }
    public function from()
    {
        return $this->belongsTo(User::class, 'from_id');
    }
}
