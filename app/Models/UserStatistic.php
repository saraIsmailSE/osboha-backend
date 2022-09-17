<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserStatistic extends Model
{
    use HasFactory;
    protected $fillable = [
        'total_new_users',
        'total_hold_users',
        'total_excluded_users',
        
    ];
}
