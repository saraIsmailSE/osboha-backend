<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'media_id',
        'comment_id',
        'post_id'
    ];
    public function post(){
        return $this->belongsTo('\App\Models\Post','post_id');
    }
    public function user(){
        return $this->belongsTo('\App\Models\User','user_id');
    }
    public function comment(){
        return $this->belongsTo('\App\Models\Comment','comment_id');
    }
    public function media(){
        return $this->hasOne('\App\Models\Media','media_id');
    }
}
