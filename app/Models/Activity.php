<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    #######ASMAA#######

    protected $fillable = [
        'name',
        'version',
        'post_id',          
    ];

    #######ASMAA#######
    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }
}
