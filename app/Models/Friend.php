<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Friend extends Model
{
    use HasFactory;
    protected $fillable = [
        'friend_id' => 'required',
        'user_id' => 'required',
    ];
    public function user()
    {
        return $this->belongsTo(User::class ,'id');
    }
}
