<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditMark extends Model
{
    use HasFactory;
    protected $fillable = [ 
        'leader_id', 'aduitMarks', 'note', 'status'
    ];

}
