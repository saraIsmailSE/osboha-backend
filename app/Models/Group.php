<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Group extends Model
{
    use HasFactory;

    protected $fillable=[
        'name',
        'description',
        'type',
        'cover_picture',
        'creator_id',
        'timeline_id'
    ];

    public function User(){
        return $this->belongsToMany(User::class,'user_groups');
    }

    public function Timeline(){
        return $this->belongsTo(Timeline::class);
    }

}