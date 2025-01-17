<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rate extends Model
{
    use HasFactory;
    protected $fillable = [
        'rate',
        'user_id',
        'comment_id',
        'post_id',
        'related_comment_id'
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }
    public function comment()
    {
        return $this->belongsTo(Comment::class);
    }

    public function relatedComment()
    {
        return $this->hasOne(Comment::class, 'id', 'related_comment_id');
    }
}
