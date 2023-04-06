<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReactionType extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'title',
        'text_color',
        'is_active',
    ];

    public function media()
    {
        return $this->hasOne(Media::class, 'reaction_type_id');
    }
}