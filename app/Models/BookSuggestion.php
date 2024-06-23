<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookSuggestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'brief',
        'publisher',
        'section_id',
        'status',
        'link',
        'user_id',
        'reviewer_id',
        'reviewer_note',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }
    public function language()
    {
        return $this->belongsTo(Language::class);
    }
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
