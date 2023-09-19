<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkHour extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "minutes",
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFormattedTimeAttribute()
    {
        $hours = floor($this->minutes / 60);
        $minutes = $this->minutes % 60;

        return sprintf("%02d:%02d", $hours, $minutes);
    }
}
