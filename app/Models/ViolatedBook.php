<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ViolatedBook extends Model
{
    use HasFactory;
    protected $fillable = [
        'book_id',
        'violation_type',
        'violated_pages',
        'description',
        'status',
        'reviewer_id',
        'reporter_id',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
    public function media()
    {
        return $this->hasMany(Media::class, 'book_report_id');
    }
}
