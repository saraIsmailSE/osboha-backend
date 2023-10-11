<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EligibleGeneralInformations extends Model
{
    use HasFactory;

    protected $fillable = [
        'reviews',
        'degree',
        'reviewer_id',
        'auditor_id',
        'general_question',
        'summary',
        'eligible_user_book_id'
    ];

    protected $with = array('reviewer', 'auditor');

    public function user_book()
    {
        return $this->belongsTo(EligibleUserBook::class, 'user_book_id');
    }
    function reviewer()
    {

        return $this->belongsTo(User::class);
    }
    public function auditor()
    {
        return $this->belongsTo(User::class);
    }
}
