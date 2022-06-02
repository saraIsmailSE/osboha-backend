<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;
    protected $fillable = [
        'creator_id' => 'required',
        'name' => 'required',
        'type' => 'required',
        'messages_status' =>'required',
    ];

public function user()
{
    return $this->hasMany(User::class,'user_id');
}
public function participant()
{
    return $this->hasMany(Participant::class,'user_id');
}
public function messages()
    {
        return $this->hasMany(Message::class,'user_id');
    }
}