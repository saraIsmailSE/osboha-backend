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
        'timeline_id',
        'is_active'
    ];

    protected $with = array('Timeline','type');


    public function users(){
        return $this->belongsToMany(User::class,'user_groups')->whereNull('user_groups.termination_reason')->withPivot('user_type','termination_reason');
    }
    public function userAmbassador(){
        return $this->belongsToMany(User::class,'user_groups')->whereNull('user_groups.termination_reason')->withPivot('user_type')->wherePivot('user_type','ambassador');
    }
    public function groupLeader(){
        return $this->belongsToMany(User::class,'user_groups')->whereNull('user_groups.termination_reason')->withPivot('user_type')->wherePivot('user_type','leader')->latest()->take(1);
    }
    public function groupSupervisor(){
        return $this->belongsToMany(User::class,'user_groups')->whereNull('user_groups.termination_reason')->withPivot('user_type')->wherePivot('user_type','supervisor')->latest()->take(1);
    }
    public function groupAdvisor(){
        return $this->belongsToMany(User::class,'user_groups')->whereNull('user_groups.termination_reason')->withPivot('user_type')->wherePivot('user_type','advisor')->latest()->take(1);
    }
    public function groupAdministrators(){
        return $this->belongsToMany(User::class,'user_groups')->whereNull('user_groups.termination_reason')->withPivot('user_type')->wherePivotIn('user_type',['advisor','supervisor','leader']);
    }
    public function leaderAndAmbassadors(){
        return $this->belongsToMany(User::class,'user_groups')->whereNull('user_groups.termination_reason')->withPivot('user_type')->wherePivotIn('user_type',['ambassador','leader']);
    }
    public function admin(){
        return $this->belongsToMany(User::class,'user_groups')->whereNull('user_groups.termination_reason')->withPivot('user_type')->wherePivot('user_type','admin');
    }

    public function Timeline(){
        return $this->belongsTo(Timeline::class,'timeline_id');
    }

    public function medias()
    {
        return $this->hasOne(Media::class);
    } 

    public function type()
    {
        return $this->belongsTo(GroupType::class,'type_id');
    }

    public function audits()
    {
        return $this->hasMany(AuditMark::class, 'group_id');
    }

}