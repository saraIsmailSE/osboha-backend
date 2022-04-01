<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mark extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'week_id',
        'out_of_90', 
        'out_of_100', 
        'total_pages',  
        'support', 
        'total_thesis', 
        'total_screenshot'
    ];

    public function week()
    {
        return $this->belongsTo(Week::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function thesis()
    {
        return $this->hasMany(Thesis::class);
    }
}
