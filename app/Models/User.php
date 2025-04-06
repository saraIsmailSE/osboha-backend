<?php

namespace App\Models;

use App\Models\Eligible\EligibleThesis;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasRoles, HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'gender',
        'request_id',
        'email_verified_at',
        'is_blocked',
        'is_hold',
        'is_excluded',
        'parent_id',
        'allowed_to_eligible',
        'last_name',
        'leader_gender',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $with = array('userProfile');


    public function userProfile()
    {
        return $this->hasOne(UserProfile::class);
    }

    public function socialMedia()
    {
        return $this->hasOne(SocialMedia::class);
    }

    public function profileMedia()
    {
        return $this->hasMany(ProfileMedia::class);
    }
    public function auditNotes()
    {
        return $this->hasMany(AuditNotes::class, 'from_id');
    }

    public function assignSupport()
    {
        return $this->hasMany(AssignSupport::class);
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }
    public function profileSetting()
    {
        return $this->hasOne(ProfileSetting::class);
    }

    public function UserException()
    {
        return $this->hasMany(UserException::class);
    }
    public function withdrawnExceptions()
    {
        return $this->UserException()->where('type_id', 6);
    }

    // public function Group(){
    //     return $this->belongsToMany(Group::class,'user_groups')->withPivot('user_type');
    // }
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'user_groups')->withPivot('user_type', 'termination_reason');
    }
    public function weekActivities()
    {
        return $this->belongsToMany(userWeekActivities::class, 'user_week_activities');
    }
    public function ambassadorsRequests()
    {
        return $this->hasMany(AmbassadorsRequests::class, 'applicant_id');
    }
    public function joinRequest()
    {
        return $this->belongsTo(AmbassadorsRequests::class, 'request_id');
    }
    public function messages()
    {
        return $this->hasMany(Message::class, 'user_id');
    }
    public function rooms()
    {
        return $this->belongsToMany(Room::class, "participants");
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
    public function reaction()
    {
        return $this->hasMany(Reaction::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function theses()
    {
        return $this->hasMany(Thesis::class);
    }

    public function pollVote()
    {
        return $this->hasMany(PollVote::class);
    }

    public function infographic()
    {
        return $this->hasMany(Infographic::class);
    }

    public function article()
    {
        return $this->hasMany(Article::class);
    }

    public function mark()
    {
        return $this->hasMany(Mark::class, 'user_id');
    }
    public function markNotes()
    {
        return $this->hasMany(MarkNote::class, 'from_id');
    }

    public function friends()
    {
        return $this->belongsToMany(User::class, 'friends', 'user_id', 'friend_id')->wherePivot('status', 1);
    }
    public function friendsOf()
    {
        return $this->belongsToMany(User::class, 'friends', 'friend_id', 'user_id')->wherePivot('status', 1);
    }
    public function notFriends()
    {
        return $this->belongsToMany(User::class, 'friends', 'user_id', 'friend_id')->wherePivot('status', 0);
    }
    public function notFriendsOf()
    {
        return $this->belongsToMany(User::class, 'friends', 'friend_id', 'user_id')->wherePivot('status', 0);
    }

    public function media()
    {
        return $this->hasMany(Media::class);
    }

    public function exception()
    {
        return $this->hasMany(UserException::class);
    }


    public function exceptionsReview()
    {
        return $this->hasMany(UserException::class, "reviewer_id");
    }


    public function userBooks()
    {
        return $this->hasMany(UserBook::class);
    }


    public function eligibleThesisReviewes()
    {
        return $this->hasMany(EligibleThesis::class, 'reviewer_id');
    }
    public function eligibleThesisAudites()
    {
        return $this->hasMany(EligibleThesis::class, 'auditor_id');
    }

    public function reportedViolations()
    {
        return $this->hasMany(ViolatedBook::class, 'reporter_id');
    }

    public function reviewedViolations()
    {
        return $this->hasMany(ViolatedBook::class, 'reviewer_id');
    }

    public function exceptionNotes()
    {
        return $this->hasMany(UserExceptionNote::class, 'from_id');
    }


    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \App\Notifications\MailResetPasswordNotification($token));
    }

    public function followupTeam()
    {
        return $this->hasOne(UserGroup::class, 'user_id', 'id')
            ->where('user_type', 'ambassador')
            ->whereNull('termination_reason');
    }
    public function children()
    {
        return $this->hasMany(User::class, 'parent_id')->where('is_excluded', 0);
    }

    public function parents()
    {
        return $this->belongsToMany(Parent::class, 'user_parents', 'user_id', 'parent_id');
    }
    public function contactsWithWithdrawns()
    {
        return $this->hasMany(ContactsWithWithdrawn::class, 'reviewer_id');
    }
    public function contactsAsAWithdrawn()
    {
        return $this->hasOne(ContactsWithWithdrawn::class, 'ambassador_id');
    }

    public function bookSuggestions()
    {
        return $this->hasMany(BookSuggestion::class);
    }
    public function discharges()
    {
        return $this->hasMany(TeamsDischarge::class);
    }
    public function MarathonViolations()
    {
        return $this->hasMany(OsbohaMarthon::class);
    }
    public function MarathonViolationReview()
    {
        return $this->hasMany(OsbohaMarthon::class);
    }

    /** Accessors */
    public function getFullNameAttribute()
    {
        return $this->name . ' ' . $this->last_name;
    }

    /** Scopes */
    public function scopeSearchName(Builder $query, string $term): Builder
    {
        $searchableFields = [
            'name',
            'last_name',
        ];
        return $query->where(function ($q) use ($term, $searchableFields) {
            foreach ($searchableFields as $field) {
                $q->orWhere($field, 'like', "%$term%");
            }
        });
    }

    // protected $searchableFields = [
    //     'name',
    //     'email',
    //     'last_name',
    // ];
}
