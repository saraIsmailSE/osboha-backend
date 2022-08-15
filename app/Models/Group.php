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
        'type_id',
        'creator_id',
        'timeline_id'
    ];

    public function users(){
        return $this->belongsToMany(User::class,'user_groups')->withPivot('user_type','termination_reason');
    }
    public function userAmbassador(){
        return $this->belongsToMany(User::class,'user_groups')->withPivot('user_type')->wherePivot('user_type','ambassador');
    }
    public function admin(){
        return $this->belongsToMany(User::class,'user_groups')->withPivot('user_type')->wherePivot('user_type','admin');
    }

    public function Timeline(){
        return $this->belongsTo(Timeline::class);
    }

    public function medias()
    {
        return $this->hasOne(Media::class);
    } 

    public function type()
    {
        return $this->belongsTo(GroupType::class);
    }

}