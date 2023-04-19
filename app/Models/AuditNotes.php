<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditNotes extends Model
{
    use HasFactory;
    protected $fillable = [
        'mark_for_audit_id',
        'from_id',
        'to_id',
        'body',
        'status',
    ];

    public function mark()
    {
        return $this->belongsTo(MarksForAudit::class, 'mark_for_audit_id');
    }
    public function from()
    {
        return $this->belongsTo(User::class ,'from_id');
    }
    public function to()
    {
        return $this->belongsTo(User::class ,'to_id');
    }



}
