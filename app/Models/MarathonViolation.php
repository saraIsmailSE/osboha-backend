<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class MarathonViolation extends Model
{
    use HasFactory;

    protected $table = 'marathon_violations';

    protected $fillable = [
        'osboha_marthon_id',
        'week_key',
        'user_id',
        'reviewer_id',
        'reviewer_note',
        'reason_id',
    ];

    public function week()
    {
        return $this->belongsTo(Week::class, 'week_key', 'week_key');
    }
    public function marathon()
    {
        return $this->belongsTo(OsbohaMarthon::class, 'osboha_marthon_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reason()
    {
        return $this->belongsTo(MarathonViolationReason::class, 'reason_id');
    }
}
