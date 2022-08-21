<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditMark extends Model
{
    use HasFactory;
    protected $fillable = [ 
        'leader_id', 'auditMarks', 'note', 'status' ,'week_id', 'auditor_id'
    ];

    public function week()
    {
        return $this->belongsTo(Week::class);
    }

    //each audit marks belongsTo specific auditor(user)
    public function auditor()
    {
        return $this->belongsTo(User::class ,'auditor_id');
    }


}
