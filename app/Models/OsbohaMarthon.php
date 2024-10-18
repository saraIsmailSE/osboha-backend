<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OsbohaMarthon extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'is_active',
    ];

    protected $with = array('marathonWeeks');

    public function marathonWeeks()
    {
        return $this->hasMany(MarathonWeek::class);
    }
    public function MarathonViolations()
    {
        return $this->hasMany(MarathonViolation::class);
    }
}
