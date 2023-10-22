<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'type_id',
        'comment_id',
        'post_id'
    ];
    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function comment()
    {
        return $this->belongsTo(Comment::class, 'comment_id');
    }
    public function media()
    {
        return $this->hasOne(Media::class, 'media_id');
    }
    public function type()
    {
        return $this->belongsTo(ReactionType::class, 'type_id');
    }
}
