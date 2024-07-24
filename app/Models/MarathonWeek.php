<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarathonWeek extends Model
{
    use HasFactory;
    protected $fillable = [
        'osboha_marthon_id',
        'week_key',
        'is_active'
    ];
    protected $with = array('week');
    protected $appends = ['week_title'];

    // Accessor to get the week title
    public function getWeekTitleAttribute()
    {
        $yearWeeks = config('constants.YEAR_WEEKS');
        foreach ($yearWeeks as $yearWeek) {
            if (isset($yearWeek['week_key']) && $yearWeek['week_key'] == $this->week_key) {
                return $yearWeek['title'];
            }
        }
        return null;
    }

    public function week()
    {
        return $this->belongsTo(Week::class, 'week_key', 'week_key');
    }
    public function osboha_marthon()
    {
        return $this->belongsTo(OsbohaMarthon::class);
    }
}
