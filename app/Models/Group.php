<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Group extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        'name',
        'description',
        'type_id',
        'creator_id',
        'timeline_id',
        'is_active'
    ];

    protected $with = array('Timeline', 'type');

    public function allUsers()
    {
        return $this->hasMany(UserGroup::class, 'group_id');
    }
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_groups')
            ->whereNull('user_groups.termination_reason')
            ->withPivot('id', 'user_type', 'termination_reason')
            ->withTimestamps()->orderByRaw("FIELD(user_type,'leader', 'supervisor','advisor','consultant', 'admin') DESC");
    }
    public function userAmbassador()
    {
        return $this->belongsToMany(User::class, 'user_groups')->whereNull('user_groups.termination_reason')->withPivot('user_type')->wherePivot('user_type', 'ambassador');
    }
    public function ambassadorsInMarathon()
    {
        return $this->belongsToMany(User::class, 'user_groups')->whereNull('user_groups.termination_reason')
            ->withPivot('user_type')->wherePivot('user_type', 'marathon_ambassador');
    }

    public function allUserAmbassador()
    {
        return $this->belongsToMany(User::class, 'user_groups')->withPivot('user_type')->wherePivot('user_type', 'ambassador');
    }
    public function groupLeader()
    {
        return $this->belongsToMany(User::class, 'user_groups')->whereNull('user_groups.termination_reason')->withPivot('user_type')->wherePivotIn('user_type', ['leader', 'special_care_leader'])->latest()->take(1);
    }

    public function groupSupportLeader()
    {
        return $this->belongsToMany(User::class, 'user_groups')->whereNull('user_groups.termination_reason')->withPivot('user_type')->wherePivot('user_type', 'support_leader')->latest()->take(1);
    }

    public function groupSupervisor()
    {
        return $this->belongsToMany(User::class, 'user_groups')->whereNull('user_groups.termination_reason')
            ->withPivot('user_type')->wherePivot('user_type', 'supervisor')->latest()->limit(1);
    }
    public function groupAdvisor()
    {
        return $this->belongsToMany(User::class, 'user_groups')->whereNull('user_groups.termination_reason')->withPivot('user_type')->wherePivot('user_type', 'advisor')->latest()->take(1);
    }
    public function groupAdministrators()
    {
        return $this->belongsToMany(User::class, 'user_groups')->whereNull('user_groups.termination_reason')->withPivot('user_type')
            ->wherePivotIn('user_type', [
                'admin', 'consultant', 'advisor', 'supervisor', 'leader', 'support_leader', 'marathon_coordinator',
                'marathon_verification_supervisor',
                'marathon_supervisor',
                'special_care_coordinator',
                'special_care_leader'
            ])->orderByRaw("FIELD(user_type,'leader', 'supervisor','advisor','consultant', 'admin') DESC");
    }
    public function leaderAndAmbassadors()
    {
        return $this->belongsToMany(User::class, 'user_groups')->whereNull('user_groups.termination_reason')->withPivot('user_type')->wherePivotIn('user_type', ['ambassador', 'leader']);
    }

    public function ambassadorsWithdrawn()
    {
        return $this->belongsToMany(User::class, 'user_groups')->where('user_groups.termination_reason', 'withdran')->withPivot('user_type')->wherePivot('user_type', 'ambassador');
    }

    public function admin()
    {
        return $this->belongsToMany(User::class, 'user_groups')->whereNull('user_groups.termination_reason')->withPivot('user_type')->wherePivot('user_type', 'admin');
    }

    public function Timeline()
    {
        return $this->belongsTo(Timeline::class, 'timeline_id');
    }

    public function medias()
    {
        return $this->hasOne(Media::class);
    }

    public function type()
    {
        return $this->belongsTo(GroupType::class, 'type_id');
    }

    public function audits()
    {
        return $this->hasMany(AuditMark::class, 'group_id');
    }

    public function AmbassadorsRequests()
    {
        return $this->hasMany(AmbassadorsRequests::class, 'group_id');
    }

    public static function boot()
    {
        parent::boot();
        self::deleting(function ($group) {
            $group->audits()->each(function ($audits) {
                $audits->delete();
            });
            $group->allUsers()->each(function ($allUsers) {
                $allUsers->delete();
            });
        });
    }
}
