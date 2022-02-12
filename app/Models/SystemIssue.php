<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemIssue extends Model
{
    use HasFactory;
    protected $fillable = [
        'reporter_id',
        'reporter_description',
        'reviewer_id',
        'reviewer_note',
        'solved',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
