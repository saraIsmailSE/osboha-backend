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

    protected $with = array('media', 'type');
    protected $appends = [/*'comments_count', 'reactions_count',*/'reacted_by_user'];

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

    public function getReactedByUserAttribute()
    {
        return $this->reactions->contains(auth()->user());
    }

    // public static function boot()
    // {
    //     parent::boot();

    //     self::deleting(function ($post) {

    //         $restrictedTypes = ['book', 'support', 'discussion', 'friday-thesis', 'book_review'];
    //         if (in_array($post->type->type, $restrictedTypes)) {
    //             return false;
    //         }

    //         $post->reactions()->get()->each(function ($reaction) {
    //             $reaction->delete();
    //         });

    //         $post->comments()->each(function ($comment) {
    //             $comment->delete();
    //         });

    //         $post->media()->each(function ($media) {
    //             if (file_exists(public_path($media->media))) {
    //                 unlink(public_path($media->media));
    //             }
    //             $media->delete();
    //         });

    //         if ($post->article) {
    //             $post->article->delete();
    //         }

    //         if ($post->activity) {
    //             $post->activity->delete();
    //         }

    //         $post->rates()->each(function ($rate) {
    //             $rate->delete();
    //         });

    //         $post->pollVotes()->each(function ($pollVote) {
    //             $pollVote->delete();
    //         });

    //         $post->pollOptions()->each(function ($pollOption) {
    //             $pollOption->delete();
    //         });


    //         $post->taggedUsers()->each(function ($taggedUser) {
    //             $taggedUser->delete();
    //         });
    //     });
    // }
}
