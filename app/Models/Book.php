<?php

namespace App\Models;

use App\Models\Eligible\EligibleUserBook;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Post;

class Book extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'writer',
        'publisher',
        'brief',
        'start_page',
        'end_page',
        'link',
        'section_id',
        'type_id',
        'level_id',
        'language_id',
        'is_active',
    ];


    protected $with = array('section', 'type', 'language', 'level');

    /**
     * Get all posts associated with book.
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function type()
    {
        return $this->belongsTo(BookType::class);
    }

    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    public function media()
    {
        return $this->hasOne(Media::class);
    }

    public function theses()
    {
        return $this->hasMany(Thesis::class);
    }

    public function userBooks()
    {
        return $this->hasMany(UserBook::class);
    }
    public function eligibleUserBook()
    {
        return $this->hasMany(EligibleUserBook::class);
    }

    public function level()
    {
        return $this->belongsTo(BookLevel::class);
    }
    public function violationReports()
    {
        return $this->hasMany(ViolatedBook::class);
    }

    public static function boot()
    {
        parent::boot();
        self::deleting(function ($book) {
            $book->userBooks()->each(function ($userBooks) {
                $userBooks->delete();
            });
            $book->posts()->each(function ($posts) {
                $posts->delete();
            });
            $book->theses()->each(function ($theses) {
                $theses->delete();
            });
        });
    }
}
