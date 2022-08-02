<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThesisType extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
    ];

    public function thesises()
    {
        return $this->hasMany(Thesis::class);
    }
}
