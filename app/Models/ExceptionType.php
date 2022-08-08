<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExceptionType extends Model
{
    use HasFactory;

    protected $fillable = [
        'type' 
    ];

    public function exceptions()
    {
        return $this->hasMany(Exception::class);
    }
}
