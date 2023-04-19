<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarksForAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'audit_marks_id',
        'mark_id',
        'status',
        'type_id',        
    ];

    protected $with = array('type','mark','auditNotes');

    public function type()
    {
        return $this->belongsTo(AuditType::class, 'type_id');
    }

    public function auditMark()
    {
        return $this->belongsTo(AuditMark::class, 'audit_marks_id');
    }
    public function mark()
    {
        return $this->belongsTo(Mark::class, 'mark_id');
    }
    public function auditNotes()
    {
        return $this->hasMany(AuditNotes::class, 'mark_for_audit_id');
    }

}
