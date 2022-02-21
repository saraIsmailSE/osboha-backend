<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timeline extends Model
{
    use HasFactory;

    protected $fillable = ['name','description' ,'type'];

    public function posts()
    {
        return $this->hasMany(Post::class,'timeline_id');
    }
    public function groups()
    {
        return $this->hasMany(Group::class,'timeline_id');
    }
    
    /* 
    public function profiles()
    {
        return $this->hasMany(Profile::class,'timeline_id');
    }
    */

    

}
