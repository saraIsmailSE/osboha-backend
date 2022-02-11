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

    /**
     * Self Relation.
     */
    public function parent()
    {
        return $this->belongsTo(Comment::class, 'comment_id');
    }
    public function children()
    {
        return $this->hasMany(Comment::class, 'comment_id');
    }

    /**
     * Get all Medias associated with comment
     * [this case will be when comment is thesis with screenshots].
    */
    public function medias()
    {
        return $this->hasMany(Media::class);
    } 

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function post()
    {
        return $this->belongsTo(Post::class. 'post_id');
    }

    
}
