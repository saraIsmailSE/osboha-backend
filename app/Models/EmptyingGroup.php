<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmptyingGroup extends Model
{
    use HasFactory;
    protected $fillable = [ 'group_id', 'user_id', 'reason', 'note' ];
    
}
