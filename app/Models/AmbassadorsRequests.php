<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmbassadorsRequests extends Model
{
    use HasFactory;
    protected $fillable = [
        'members_num',
        'ambassadors_gender',
        'leader_gender',
        'applicant_id',
        'group_id',
        'high_priority',
        'is_done',
    ];
    public function applicant()
    {
        return $this->belongsTo(User::class, 'applicant_id');
    }
    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }
    public function ambassadors()
    {
        return $this->hasMany(User::class, 'request_id');
    }
}
