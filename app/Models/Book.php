<?php

namespace App\Models;

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
        'section',
        'type',
        'level',
        'post_id',
    ];

    /**
     * Get all posts associated with book.
    */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}

