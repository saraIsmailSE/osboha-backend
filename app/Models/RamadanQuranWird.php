<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RamadanQuranWird extends Model
{
    protected $fillable = ['user_id', 'ramadan_day_id', 'no_of_parts'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ramadanDay()
    {
        return $this->belongsTo(RamadanDay::class);
    }
}
