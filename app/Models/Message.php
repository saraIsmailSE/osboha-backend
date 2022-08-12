<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;
    protected $fillable = [
            'sender_id',
            'receiver_id'
    ];
    public function user()
    {
        return $this->belongsTo(User::class ,'user_id');
    }
    public function room()
    {
        return $this->belongsTo(User::class ,'user_id');
    }
}
