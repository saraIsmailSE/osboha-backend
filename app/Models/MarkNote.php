<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarkNote extends Model
{
    use HasFactory;
    protected $fillable = [
        'mark_id',
        'from_id',
        'body',
        'status',
    ];
    public function mark()
    {
        return $this->belongsTo(Mark::class, 'mark_id');
    }
    public function from()
    {
        return $this->belongsTo(User::class, 'from_id');
    }
}
