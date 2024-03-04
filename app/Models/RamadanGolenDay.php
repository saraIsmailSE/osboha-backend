<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RamadanGolenDay extends Model
{
    protected $fillable = [
        'user_id',
        'ramadan_day_id',
        'sunan_al_rawatib',
        'tasbeeh',
        'istighfar',
        'duha_prayer',
        'morning_evening_dhikr',
        'shaf_and_witr',
        'suhoor',
        'drink_water',
        'sleep_amount',
        'brushing_teeth',
        'contemplation_of_allahs_signs',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ramadanDay()
    {
        return $this->belongsTo(RamadanDay::class);
    }
}
