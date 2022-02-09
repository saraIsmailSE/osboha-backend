<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RejectedMark extends Model
{
    use HasFactory;

    protected $fillable = [
        'rejecter_note', 
        'is_acceptable'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function thesis()
    {
        return $this->belongsTo(Thesis::class);
    }

    public function week()
    {
        return $this->belongsTo(Week::class);
    }

    

    
}
