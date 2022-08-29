<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;
    protected $fillable = [
        'creator_id',
        'name' ,
        'type' ,
        'messages_status' ,
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, "participants");
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'user_id');
    }
}