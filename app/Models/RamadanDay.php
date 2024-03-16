<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RamadanDay extends Model
{
    protected $fillable = ['day', 'is_active', 'created_at', 'updated_at'];

    public function ramadanQuranWirds()
    {
        return $this->hasMany(RamadanQuranWird::class);
    }

    public function ramadanNightPrayers()
    {
        return $this->hasMany(RamadanNightPrayer::class);
    }

    public function ramadanQuestions()
    {
        return $this->hasMany(RamadanQuestion::class);
    }

    public function hadiths()
    {
        return $this->hasMany(RamadanHadith::class);
    }

    public function goldenDays()
    {
        return $this->hasMany(RamadanGolenDay::class);
    }
}
