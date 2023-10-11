<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EligibleQuotation extends Model
{
    use HasFactory;
    
    protected $table = 'eligible_quotations';

    protected $fillable = [

        'text',
        "question_id"
    ];

    public function question(){
        return $this->belongsTo(EligibleQuestion::class,'question_id');
    }
}
