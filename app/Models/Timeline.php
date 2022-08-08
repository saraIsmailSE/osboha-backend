<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timeline extends Model
{
    use HasFactory;

    protected $fillable = ['name','description' ,'type_id'];

    public function posts()
    {
        return $this->hasMany(Post::class,'timeline_id');
    }
    public function groups()
    {
        return $this->hasMany(Group::class,'timeline_id');
    }

    public function type(){
        return $this->belongsTo(TimelineType::class);
    }
    
    public function profiles()
    {
        return $this->hasMany(UserProfile::class,'timeline_id');
    }
    

}
