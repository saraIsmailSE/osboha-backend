<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RamadanHadith extends Model
{
    protected $fillable = ['hadith', 'ramadan_day_id'];

    public function ramadanDay()
    {
        return $this->belongsTo(RamadanDay::class);
    }

    public function memorization()
    {
        return $this->hasMany(RamadanHadithMemorization::class, 'ramadan_hadiths_id');
    }
}
