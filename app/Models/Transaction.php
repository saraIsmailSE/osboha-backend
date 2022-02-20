<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'role_id',
        'hiring_date',
        'termination_date',
        'status',
        'solved',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
