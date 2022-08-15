<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
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
       // 'user_type',
        'email_verified_at',
        'is_blocked',
        'is_hold',
        'is_excluded',
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

    public function userProfile(){
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
    

    public function profileSetting()
    {
        return $this->hasOne(ProfileSetting::class);
    } 
    
    public function UserException(){
        return $this->hasMany(UserException::class);
    }

    // public function Group(){
    //     return $this->belongsToMany(Group::class,'user_groups')->withPivot('user_type');
    // }
    public function groups(){
        return $this->belongsToMany(Group::class,'user_groups')->withPivot('user_type','termination_reason');
    }
    public function LeaderRrequest()
    {
        return $this->hasMany(leader_request::class,'leader_id');
    }
    public function AmbassadorRrequest()
    {
        return $this->belongsToOne(leader_request::class);
    }
    public function messages()
    {
        return $this->hasMany(Message::class,'user_id');
    }
    public function rooms()
    {
        return $this->belongsToMany(Room::class,"participants");
    }

    public function posts(){
        return $this->hasMany(Post::class);
    }
    public function reaction(){
        return $this->hasMany(Reaction::class);
    }

    public function comment(){
        return $this->hasMany(Comment::class);
    }

    public function thesis(){
        return $this->hasMany(Thesis::class);
    }

    public function pollVote(){
        return $this->hasMany(PollVote::class);
    }

    public function infographic(){
        return $this->hasMany(Infographic::class);
    }

    public function article(){
        return $this->hasMany(Article::class);
    }

    public function mark(){
        return $this->hasMany(Mark::class);
    }

    public function rejectedMark(){
        return $this->hasMany(RejectedMark::class);
    }

    public function friend(){
        return $this->hasMany(Friend::class);
    }

    public function transaction(){
        return $this->hasMany(Transaction::class);
    }

    public function media(){
        return $this->hasMany(Media::class);
    }
}

