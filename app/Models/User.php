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

    public function socialMedia()
    {
        return $this->hasMany(SocialMedia::class,'user_id');
    }

    public function UserException(){
        return $this->hasMany(UserException::class);
    }

    public function Group(){
        return $this->belongsToMany(Group::class,'user_groups')->withPivot('user_type');
    }
    public function messages()
    {
        return $this->hasMany(Message::class,'user_id');
    }
    public function participant()
    {
        return $this->hasMany(Participant::class,'user_id');
    }
}

