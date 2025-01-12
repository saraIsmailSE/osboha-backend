<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        "question",
        "status",
        "discussion_type",
        "moved_to_discussion_by",
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

    public function movedToDiscussionBy()
    {
        return $this->belongsTo(User::class, 'moved_to_discussion_by');
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
                    "name" => $this->user->parent->name . ($this->user->parent->last_name ? ' ' . $this->user->parent->last_name : '')
                ]
            ];
        }
        //if advisor, return consultant
        else if ($this->user->hasRole('advisor')) {
            return [
                [
                    "role" => "المستشار",
                    "name" => $this->user->parent->name . ($this->user->parent->last_name ? ' ' . $this->user->parent->last_name : '')
                ],
            ];
        }
        //if supervisor, return advisor and consultant
        else if ($this->user->hasRole('supervisor')) {
            $parent = $this->user->parent;
            return [
                [
                    "role" => "الموجه",
                    "name" => $parent->name . ($parent->last_name ? ' ' . $parent->last_name : '')
                ],
                [
                    "role" => "المستشار",
                    "name" => $parent->parent->name . ($parent->parent->last_name ? ' ' . $parent->parent->last_name : '')
                ]

            ];
        }
        //if leader, return supervisor, advisor and consultant
        else if ($this->user->hasRole('leader')) {
            $parent = $this->user->parent;
            return [
                [
                    "role" => "المراقب",
                    "name" => $parent->name . ($parent->last_name ? ' ' . $parent->last_name : '')
                ],
                [
                    "role" => "الموجه",
                    "name" => $parent->parent->name . ($parent->parent->last_name ? ' ' . $parent->parent->last_name : '')
                ],
                [
                    "role" => "المستشار",
                    "name" => $parent->parent->parent->name . ($parent->parent->parent->last_name ? ' ' . $parent->parent->parent->last_name : '')
                ]
            ];
        }
    }

    public function getIsAnsweredLateAttribute()
    {
        $answers = DB::table('questions_assignees')
            ->join('answers', function ($join) {
                $join->on('questions_assignees.question_id', '=', 'answers.question_id')
                    ->where('answers.is_discussion', false)
                    ->where('answers.user_id', $this->current_assignee_id)
                    ->where('answers.question_id', $this->id)
                    ->whereRaw('answers.created_at < DATE_ADD(questions_assignees.created_at, INTERVAL 12 HOUR)');
            })
            ->where('questions_assignees.is_active', true)
            ->count();

        return $answers <= 0;
    }

    public function getCurrentAssigneeCreatedAtAttribute()
    {
        $assignee = $this->assignees->where('assignee_id', $this->current_assignee_id)->first();
        return $assignee ? $assignee->created_at : null;
    }
}
