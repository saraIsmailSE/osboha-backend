<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Timeline;

class Post extends Model
{
    use HasFactory;
    protected $fillable = [
        'body',
        'user_id',
        'type_id',
        'allow_comments',
        'tag',
        'vote',
        'is_approved',
        'is_pinned',
        'timeline_id',
        'book_id',
    ];

    public function comments()
    {
        return $this->hasMany(Comment::class, 'post_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function Timeline()
    {
        return $this->belongsTo(Timeline::class, 'timeline_id');
    }

    public function book()
    {
        return $this->belongsTo(Book::class, 'book_id');
    }

    public function media()
    {
        return $this->hasMany(Media::class, 'post_id');
    }

    public function article()
    {
        return $this->hasOne(Article::class, 'post_id');
    }

    public function activity()
    {
        return $this->hasOne(Activity::class, 'post_id');
    }

    public function type()
    {
        return $this->belongsTo(PostType::class);
    }

    public function rates(){
        return $this->hasMany(Rate::class, 'post_id');
    }
}
