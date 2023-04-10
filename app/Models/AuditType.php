<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditType extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
    ];

    public function marksForAudit()
    {
        return $this->hasMany(MarksForAudit::class, 'type_id');
    }

}


