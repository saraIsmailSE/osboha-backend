<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditMark extends Model
{
    use HasFactory;
    protected $fillable = [ 
        'group_id', 'status' ,'week_id', 'auditor_id'
    ];

    protected $with = array('marksForAudit');

    public function week()
    {
        return $this->belongsTo(Week::class);
    }
    public function group()
    {
        return $this->belongsTo(Group::class ,'group_id');
    }

    //each audit marks belongsTo specific aduitor(user)
    public function auditor()
    {
        return $this->belongsTo(User::class ,'auditor_id');
    }
    public function marksForAudit()
    {
        return $this->hasMany(MarksForAudit::class, 'audit_marks_id');
    }
    
}
