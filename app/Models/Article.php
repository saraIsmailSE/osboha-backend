<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    #######ASMAA#######

    protected $fillable = [
        'title',
        'post_id',
        'user_id',
        'section',        
    ];

    #######ASMAA#######
    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    #######ASMAA#######
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
