<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupType extends Model
{
    use HasFactory;
<<<<<<< HEAD
    protected $fillable=[
        'type'
    ];
=======

    protected $fillable = [
        'type' 
    ];

    public function groups()
    {
        return $this->hasMany(Group::class);
    }
>>>>>>> f8263cc8d84c69b7cd7445f682b3fe4492efe3ed
}
