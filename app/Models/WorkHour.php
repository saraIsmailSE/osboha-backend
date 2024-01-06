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
        "week_id",
        "created_at",
    ];

    protected $appends = ['date'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function week()
    {
        return $this->belongsTo(Week::class);
    }

    public function getDateAttribute()
    {
        return $this->created_at->format('Y-m-d');
    }
}
