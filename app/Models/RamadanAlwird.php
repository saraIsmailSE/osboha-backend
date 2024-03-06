<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RamadanAlwird extends Model
{
    protected $fillable = [
        'user_id',
        'ramadan_day_id',
        'number_juzu_read',
        'points'
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
