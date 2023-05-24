<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
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
        'parent_id'
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

    // public function Group(){
    //     return $this->belongsToMany(Group::class,'user_groups')->withPivot('user_type');
    // }
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'user_groups')->withPivot('user_type', 'termination_reason');
    }
    public function LeaderRrequest()
    {
        return $this->hasMany(leader_request::class, 'leader_id');
    }
    public function AmbassadorRrequest()
    {
        return $this->belongsToOne(leader_request::class);
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
        return $this->hasMany(Mark::class);
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

    public function transaction()
    {
        return $this->hasMany(Transaction::class);
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

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \App\Notifications\MailResetPasswordNotification($token));
    }
}