<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',  
        'timeline_id',
        'first_name_ar',
        'middle_name_ar',
        'last_name_ar',
        'country',
        'resident',
        'phone',
        'occupation',
        'religion',
        'birthdate',
        'bio',
        'cover_picture',
        'fav_writer',
        'fav_book',
        'fav_section',
        'fav_quote',
        'extraspace'
    ];

    public function user(){
        return $this->belongsTo(User::class, 'user_id' );
    }

    public function Timeline(){
        return $this->belongsTo( Timeline::class, 'timeline_id' );
    }
}
