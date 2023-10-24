<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        "question",
        "status",
        "user_id",
        "assignee_id"

    ];

    protected $appends = [
        "user_parents"
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, "assignee_id");
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    public function getUserParentsAttribute()
    {
        //if admin , no parent
        if ($this->user->hasAnyRole(['admin'])) {
            return [];
        } else if ($this->user->hasRole('consultant')) {
            return [
                [
                    "role" => "المسؤول",
                    "name" => $this->user->parent->name
                ]
            ];
        }
        //if advisor, return consultant
        else if ($this->user->hasRole('advisor')) {
            return [
                [
                    "role" => "المستشار",
                    "name" => $this->user->parent->name
                ]
            ];
        }
        //if supervisor, return advisor and consultant
        else if ($this->user->hasRole('supervisor')) {
            $parent = $this->user->parent;
            return [
                [
                    "role" => "الموجه",
                    "name" => $parent->name
                ],
                [
                    "role" => "المستشار",
                    "name" => $parent->parent->name
                ]

            ];
        }
        //if leader, return supervisor, advisor and consultant
        else if ($this->user->hasRole('leader')) {
            $parent = $this->user->parent;
            return [
                [
                    "role" => "المراقب",
                    "name" => $parent->name
                ],
                [
                    "role" => "الموجه",
                    "name" => $parent->parent->name
                ],
                [
                    "role" => "المستشار",
                    "name" => $parent->parent->parent->name
                ]
            ];
        }
    }
}
