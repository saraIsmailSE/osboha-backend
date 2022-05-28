<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;
    protected $fillable = [
            'sender_id' => 'required',
            'receiver_id' => 'required',
            'status' => 'required',
            'room_id' => 'required',
    ];
    public function user()
    {
        return $this->belongsTo(User::class ,'user_id');
    }
    public function participant()
    {
        return $this->hasMany(Participant::class,'user_id');
    }
}
}