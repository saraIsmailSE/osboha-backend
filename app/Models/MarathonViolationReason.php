<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarathonViolationReason extends Model
{
    use HasFactory;
    protected $table = 'marathon_violations_reasons';

    protected $fillable = ['reason', 'points', 'is_active'];

    public function violations()
    {
        return $this->hasMany(MarathonViolation::class, 'reason_id');
    }
}
