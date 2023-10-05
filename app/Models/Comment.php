<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Post;
use App\Models\Media;

class Comment extends Model
{
    use HasFactory;
    protected $fillable = [
        'body',
        'user_id',
        'post_id',
        'comment_id',
        'type',
    ];

    protected $withCount = ['reactions'];
    protected $with = ['user.roles', 'media', 'replies'];
    protected $appends = ['is_liked'];
    /**
     * Self Relation.
     * replies relation means that this model(comment) has many replies
     * comment relation is the reverse relation of replies relation 
     */
    public function comment()
    {
        return $this->belongsTo(Comment::class);
    }
    public function replies()
    {
        return $this->hasMany(Comment::class);
    }

    public function media()
    {
        return $this->hasOne(Media::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function post()
    {
        return $this->belongsTo(Post::class . 'post_id');
    }

    public function thesis()
    {
        return $this->hasOne(Thesis::class);
    }

    public function reactions()
    {
        return $this->hasMany(Reaction::class)->where('type_id', 1); // 1 is the id of like type (for now)
    }

    public function getIsLikedAttribute()
    {
        return $this->reactions->where('user_id', auth()->id())->where('type_id', 1)->count() > 0;
    }
}
