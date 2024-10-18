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
        'week_key',
        'created_at',

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
    public function marathonWeeks()
    {
        return $this->hasMany(MarathonWeek::class, 'week_key', 'week_key');
    }
}
