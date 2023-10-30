<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EligibleCertificates extends Model
{
    use HasFactory;

    protected $fillable = [
        'eligible_user_books_id',
        "final_grade",
        'general_summary_grade',
        "thesis_grade",
        "check_reading_grade",
    ];

    public function user_book(){
        return $this->belongsTo(EligibleUserBook::class,'eligible_user_books_id');
    }
}
