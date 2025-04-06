<?php

namespace App\Models\Eligible;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EligibleQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'reviews',
        'degree',
        'reviewer_id',
        'auditor_id',
        'question',
        'eligible_user_books_id',
        "starting_page",
        "ending_page"
    ];

    protected $with = array('quotation','reviewer','auditor');


    public function user_book(){
        return $this->belongsTo(EligibleUserBook::class,'eligible_user_books_id');
    }
    function reviewer(){

        return $this->belongsTo(User::class);
    }
    public function auditor(){
        return $this->belongsTo(User::class);
    }


    public function quotation(){
        return $this->hasMany(EligibleQuotation::class);
    }

    public static function boot()
    {
        parent::boot();
        self::deleting(function ($question) {
            $question->quotation()->each(function ($quotation) {
                $quotation->delete();
            });
        });
    }

}
