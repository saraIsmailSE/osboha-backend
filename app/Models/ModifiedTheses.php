<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModifiedTheses extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'thesis_id',
        'week_id',
        'modifier_id',
        'modifier_reason_id',
        'head_modifier_id',
        'head_modifier_reason_id',
        'status'

    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function thesis()
    {
        return $this->belongsTo(Thesis::class);
    }

    public function week()
    {
        return $this->belongsTo(Week::class);
    }

    public function modifier()
    {
        return $this->belongsTo(User::class, 'modifier_id');
    }

    public function modifierReason()
    {
        return $this->belongsTo(ModificationReason::class, 'modifier_reason_id');
    }

    public function headModifier()
    {
        return $this->belongsTo(User::class, 'head_modifier_id');
    }

    public function headModifierReason()
    {
        return $this->belongsTo(ModificationReason::class, 'head_modifier_reason_id');
    }
}