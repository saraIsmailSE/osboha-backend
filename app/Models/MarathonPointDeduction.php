<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class MarathonPointDeduction extends Model
{
    use HasFactory;

    protected $table = 'marathon_point_deduction';

    protected $fillable = [
        'osboha_marthon_id',
        'week_key',
        'user_id',
        'reviewer_id',
        'reason',
    ];

    public function marathon()
    {
        return $this->belongsTo(OsbohaMarathon::class, 'osboha_marthon_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
