<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasFactory;
    protected $fillable = [
        'type',
        'media',
        'user_id',
        'post_id',
        'comment_id',
        'infographic_id',
        'infographic_series_id',
        'book_id',
        'user_exception_id',
        'reaction_type_id',
        'group_id',
        'book_report_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function posts()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }
    public function comments()
    {
        return $this->belongsTo(Comment::class, 'comment_id');
    }
    public function reactionType()
    {
        return $this->belongsTo(ReactionType::class, 'reaction_type_id');
    }
    public function infographics()
    {
        return $this->belongsTo(Infographic::class, 'infographic_id');
    }
    public function series()
    {
        return $this->belongsTo(InfographicSeries::class, 'infographic_series_id');
    }

    public function book()
    {
        return $this->belongsTo(Book::class, 'book_id');
    }
    public function bookReort()
    {
        return $this->belongsTo(ViolatedBook::class, 'book_report_id');
    }
    public function userEexception()
    {
        return $this->belongsTo(UserException::class, 'user_exception_id');
    }
}
