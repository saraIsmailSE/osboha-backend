<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;

    protected $fillable = [
        'section',  
    ];

    public function books()
    {
        return $this->hasMany(Book::class);
    }

    public function infographics()
    {
        return $this->hasMany(Infographic::class);
    }

    public function infographiSeries()
    {
        return $this->hasMany(InfographicSeries::class);
    }

}
