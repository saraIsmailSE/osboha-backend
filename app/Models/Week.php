<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Week extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'title',
        'is_vacation',
        'main_timer',
        'audit_timer',
        'modify_timer',

    ];

    public function exception()
    {
        return $this->hasMany(UserException::class);
    }
    public function assignSupport()
    {
        return $this->hasMany(AssignSupport::class);
    }
    public function usersActivities()
    {
        return $this->belongsToMany(userWeekActivities::class, 'user_week_activities');
    }
}
