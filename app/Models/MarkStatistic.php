<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarkStatistic extends Model
{
    use HasFactory;
    protected $fillable = [
        'general_average_reeding',
        'total_users_have_100',
        'total_pages',
        'total_thesis',
        
    ];
}
