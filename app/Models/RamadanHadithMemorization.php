<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RamadanHadithMemorization extends Model
{
    protected $fillable = [
        'ramadan_hadith_id',
        'user_id',
        'status',
        'points',
        'reviews',
        'reviewer_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function hadith()
    {
        return $this->belongsTo(RamadanHadith::class, 'ramadan_hadith_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
