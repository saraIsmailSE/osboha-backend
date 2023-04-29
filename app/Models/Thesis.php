<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Thesis extends Model
{
    use HasFactory;

    ####Asmaa###

    protected $fillable = [
        'comment_id',
        'user_id',
        'max_length',
        'book_id',
        'type_id',
        'mark_id',
        'start_page',
        'end_page',
        'total_screenshots',
        'status',
    ];

    public function comment()
    {
        return $this->belongsTo(Comment::class, 'comment_id');
    }

    public function mark()
    {
        return $this->belongsTo(Mark::class, 'mark_id');
    }

    public function book()
    {
        return $this->belongsTo(Book::class, 'book_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function type()
    {
        return $this->belongsTo(ThesisType::class);
    }
}