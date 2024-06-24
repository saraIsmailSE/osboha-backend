<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamsDischarge extends Model
{
    use HasFactory;
    protected $table = 'teams_discharge';

    protected $fillable = ['group_id', 'user_id', 'reason', 'note'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }
}
