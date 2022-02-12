<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasFactory;
    protected $fillable = [
        'type' => 'required',
        'user_id' => 'required'

    ];

    public function user()
    {
        return $this->belongsTo(User::class ,'user_id');
    }
    
 public function posts()
    {
        return $this->belongsTo(Post::class,'post_id');
    }
    public function comments()
    {
        return $this->belongsTo(Comment::class,'comment_id');
    }
}

