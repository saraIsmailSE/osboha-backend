<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupType extends Model
{
    use HasFactory;

    protected $fillable = [
        'type' 
    ];

    public function groups()
    {
        return $this->hasMany(Group::class,'type_id');
    }
}