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
    public function week()
    {
        return $this->belongsTo(Week::class);
    }
    public function osboha_marthon()
    {
        return $this->belongsTo(OsbohaMarthon::class);
    }
}
