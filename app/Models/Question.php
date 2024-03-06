<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        "question",
        "status",
        "user_id",
        "current_assignee_id",
        "closed_at"
    ];

    protected $appends = [
        "user_parents",
        "is_answered_late",
        'current_assignee_created_at',
    ];

    protected $with = ['media'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function currentAssignee()
    {
        return $this->belongsTo(User::class, "current_assignee_id");
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    public function assignees()
    {
        return $this->hasMany(QuestionAssignee::class);
    }

    public function media()
    {
        return $this->hasMany(Media::class);
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
                ],
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

    public function getIsAnsweredLateAttribute()
    {
        $currentAssigneeCreatedAt = $this->assignees->where('assignee_id', $this->current_assignee_id)->first()->created_at;
        $answers = $this->answers->where('user_id', $this->current_assignee_id)
            ->where('is_discussion', false)
            ->where('created_at', '<', Carbon::parse($currentAssigneeCreatedAt)->addHours(12))
            ->count();

        return $answers === 0;
    }

    public function getCurrentAssigneeCreatedAtAttribute()
    {
        $assignee = $this->assignees->where('assignee_id', $this->current_assignee_id)->first();
        return $assignee ? $assignee->created_at : null;
    }
}
