<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserParent extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'parent_id',
        'is_active',
    ];

    public function child()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }
}
