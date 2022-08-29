<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookStatistics extends Model
{
    use HasFactory;
    protected $fillable = [
        'total',
        'simple',
        'intermediate',
        'advanced',
        'method_books',
        'ramadan_books',
        'children_books',
        'young_people_books',
        
    ];
}
