<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Week extends Model
{
    use HasFactory;

    protected $fillable = [
        // 'date',
        'title',
        'is_vacation',
    ];

    public function marks()
    {
        return $this->hasMany(Mark::class);
    }

    public function exceptions()
    {
        return $this->hasMany(Exception::class);
    }
}