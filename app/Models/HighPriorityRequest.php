<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HighPriorityRequest extends Model
{
    use HasFactory;
    protected $fillable = [
        'request_id',
    ];
    public function leader_requests()
    {
        $this->hasMany(leader_requests::class);
    }
}
