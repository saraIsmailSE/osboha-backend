<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EligibleGeneralThesis extends Model
{
    use HasFactory;

    protected $table = 'eligible_general_thesis';

    protected $fillable = [
        'reviews',
        'degree',
        'reviewer_id',
        'auditor_id',
        'thesis_text',
        'starting_page',
        'ending_page',
        'eligible_user_book_id'
    ];

    protected $with = array('reviewer','auditor');


    public function user_book(){
        return $this->belongsTo(EligibleUserBook::class,'user_book_id');
    }

    public function photos(){
        return $this->hasMany(Photos::class);
    }
    function reviewer(){
 
        return $this->belongsTo(User::class);
    }
    public function auditor(){
        return $this->belongsTo(User::class);
    }

    public static function boot() {
        parent::boot();
        self::deleting(function($thesis) { // before delete() method call this
             $thesis->photos()->each(function($photo) {
                $photo->delete(); // <-- direct deletion
             });
        });
    }

}
