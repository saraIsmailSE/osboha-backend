<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignSupport extends Model
{
    use HasFactory;

    protected $fillable = [ 
        'reason', 'week_id', 'user_id'
    ];


    protected $with = array('week', 'auther');

    public function week()
    {
        return $this->belongsTo(Week::class);
    }
    public function auther()
    {
        return $this->belongsTo(User::class ,'user_id');
    }

}
