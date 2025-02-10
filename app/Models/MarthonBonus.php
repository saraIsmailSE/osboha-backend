<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarthonBonus extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'osboha_marthon_id',
        'week_key',
        'activity',
        'leading_course',
        'eligible_book',
        'eligible_book_less_VG',
    ];
    public function osboha_marthon()
    {
        return $this->belongsTo(OsbohaMarthon::class);
    }
    public function week()
    {
        return $this->belongsTo(Week::class, 'week_key', 'week_key');
    }
}
