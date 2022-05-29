<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditMark extends Model
{
    use HasFactory;
    protected $fillable = [ 
        'leader_id', 'aduitMarks', 'note', 'status' ,'week_id', 'aduitor_id'
    ];

    public function week()
    {
        return $this->belongsTo(Week::class);
    }

    //each audit marks belongsTo specific aduitor(user)
    public function aduitor()
    {
        return $this->belongsTo(User::class ,'aduitor_id');
    }


}
