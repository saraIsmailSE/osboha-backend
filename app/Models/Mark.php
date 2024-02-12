<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Mark extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'week_id',
        'reading_mark',
        'writing_mark',
        'total_pages',
        'support',
        'total_thesis',
        'total_screenshot',
        'is_freezed'
    ];
    protected $with = array('user', 'week');

    public function week()
    {
        return $this->belongsTo(Week::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function thesis()
    {
        return $this->hasMany(Thesis::class);
    }
    public function pendingThesisCount()
    {
        return $this->hasMany(Thesis::class)
            ->where('status', 'pending')
            ->selectRaw('mark_id, count(*) as pending_thesis')
            ->groupBy('mark_id');
    }

    public function audit()
    {
        return $this->hasMany(MarksForAudit::class, 'mark_id');
    }
    // public function out_of_100()
    // {

    //     return $this->reading_mark + $this->writing_mark + $this->support;
    // }
}
