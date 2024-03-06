<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RamadanQuestion extends Model
{
    protected $fillable = [
        'question',
        'ramadan_day_id',
        'time_to_publish',
        'category',
    ];

    public function ramadanDay()
    {
        return $this->belongsTo(RamadanDay::class);
    }
}
