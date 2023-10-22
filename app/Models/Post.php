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
        'tags',
        'is_approved',
        'is_pinned',
        'timeline_id',
        'book_id',
    ];

    protected $with = array('media');
    protected $appends = ['comments_count', 'reactions_count', 'reacted_by_user'];

    public function comments()
    {
        return $this->hasMany(Comment::class, 'post_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function timeline()
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

    public function rates()
    {
        return $this->hasMany(Rate::class, 'post_id');
    }

    public function pollOptions()
    {
        return $this->hasMany(PollOption::class, 'post_id')->withCount('votes');
    }

    public function pollVotes()
    {
        return $this->hasMany(PollVote::class, 'post_id');
    }

    public function taggedUsers()
    {
        return $this->hasMany(TaggedUser::class, 'post_id');
    }

    public function reactions()
    {
        //get likes only (just for now)
        return $this->belongsToMany(User::class, 'reactions', 'post_id', 'user_id')->withPivot('type_id')->withTimestamps();
    }

    //attributes
    public function getCommentsCountAttribute()
    {
        return $this->comments()->count();
    }

    public function getReactionsCountAttribute()
    {
        return $this->reactions()->count();
    }

    public function getReactedByUserAttribute()
    {
        return $this->reactions->contains(auth()->user());
    }
}
