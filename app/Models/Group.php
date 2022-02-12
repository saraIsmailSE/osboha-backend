<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Group extends Model
{
    use HasFactory;

    protected $fillable=[
        'name',
        'description',
        'type',
        'cover_picture',
        'creator_id'
    ];

    public function User(){
        return $this->hasMany(User::class);
    }

}