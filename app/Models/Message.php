<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'room_id',
        "message_id",
        "body",
        'status',
    ];
    public function sender()
    {
        return $this->belongsTo(User::class);
    }

    public function receiver()
    {
        return $this->belongsTo(User::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
    public function media()
    {
        return $this->hasMany(Media::class);
    }

    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    public function replies()
    {
        return $this->hasMany(Message::class);
    }
}
