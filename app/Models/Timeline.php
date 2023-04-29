<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timeline extends Model
{
    use HasFactory;

    protected $fillable = ['type_id'];

    // protected $with = array('posts');

    public function posts()
    {
        return $this->hasMany(Post::class, 'timeline_id');
    }
    public function group()
    {
        return $this->hasOne(Group::class, 'timeline_id');
    }

    public function type()
    {
        return $this->belongsTo(TimelineType::class);
    }

    public function profile()
    {
        return $this->hasOne(UserProfile::class, 'timeline_id');
    }
}