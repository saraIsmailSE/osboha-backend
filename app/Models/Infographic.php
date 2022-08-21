<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Infographic extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'media_id',
        'designer_id',
        'section_id',
        'series_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'designer_id');
    }

    public function series()
    {
        return $this->belongsTo(InfographicSeries::class, 'series_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }
    
    public function media()
    {
        return $this->hasOne(Media::class, 'infographic_id');
    }
}