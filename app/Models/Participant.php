<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id' => 'required',
        'room_id' => 'required',
        'type' => 'required',
    ];
public function user()
{
    return $this->belongsTo(User::class ,'user_id');
}
public function room()
{
    return $this->belongsTo(Room::class ,'user_id');
}
}