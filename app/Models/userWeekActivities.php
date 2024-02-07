<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class userWeekActivities extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'week_id',
    ];
    public function week()
    {
        return $this->belongsTo(Week::class, 'week_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
