<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarathonWeek extends Model
{
    use HasFactory;
    protected $fillable = [
        'week_id',
        'is_active'
    ];
    public function week()
    {
        return $this->belongsTo(Week::class);
    }
}
