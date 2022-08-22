<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CPanel extends Model
{
    use HasFactory;
    protected $fillable=[
        'user_id',
        'group_id',
        'user_type',
        'email'
    ];
}