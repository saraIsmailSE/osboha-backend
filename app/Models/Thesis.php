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
        'type',
        'mark_id',
        'total_pages',
        'total_screenshots',
        'is_acceptable',
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

    public function thesis_type()
    {
        return $this->belongsTo(ThesisType::class, 'thesis_type_id');
    }
}
