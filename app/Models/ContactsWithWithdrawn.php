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
        'ambassador_id',
        'reviewer_id',
    ];
    protected $with = ['reviewer'];

    public function ambassador()
    {
        return $this->belongsTo(User::class, 'ambassador_id');
    }
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
