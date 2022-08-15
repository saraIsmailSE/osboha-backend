<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimelineType extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'description' ,'type' 
    ];

   public function timelines(){
        return $this->hasMany( Timeline::class,"type_id");
    }
}
