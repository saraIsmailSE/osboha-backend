<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RamadanNightPrayer extends Model
{
    protected $fillable = [
        'user_id',
        'ramadan_day_id',
        'no_of_rakaat',
        'night_pray',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ramadanDay()
    {
        return $this->belongsTo(RamadanDay::class, 'ramadan_day_id');
    }
}
