<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Infographic extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'designer_id',
        'section',
        'series_id',
    ];

    public function series()
    {
        $this->belongsTo(InfographicSeries::class, 'series_id');
    }

    public function media()
    {
        return $this->hasOne(Media::class, 'infographic_id');
    }
}
