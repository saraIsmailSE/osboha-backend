<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Timeline;

class Post extends Model
{
    use HasFactory;
    public function Media()
    {
        return $this->hasMany(Media::class,'post_id');
    }

  
}
