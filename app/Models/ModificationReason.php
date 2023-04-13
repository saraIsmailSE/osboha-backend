<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModificationReason extends Model
{
    use HasFactory;

    protected $table = 'modification_reasons';
    protected $fillable = [
        'reason',
        'level',
    ];
}