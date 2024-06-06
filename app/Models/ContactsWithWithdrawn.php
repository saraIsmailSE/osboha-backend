<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactsWithWithdrawn extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact',
        'return',
        'reviewer_id',
    ];

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
