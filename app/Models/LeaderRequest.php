<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaderRequest extends Model
{


    use HasFactory;
    protected $fillable = [
        'members_num',
        'gender',
        'leader_id',
        'current_team_count',
    ];
    public function user()
    {
        $this->belongsTo(user::class);
    }
}
