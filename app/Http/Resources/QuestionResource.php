<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            "id" => $this->id,
            "question" => $this->question,
            "status" => $this->status,
            "user" =>  UserInfoResource::make($this->user),
            "parent" => $this->user->parent ? UserInfoResource::make($this->user->parent) : null,
            "assignee" => UserInfoResource::make($this->currentAssignee),
            "answers" => count($this->answers) > 0 ? AnswerResource::collection($this->answers) : [],
            "management_team" => UserManagementTeamResource::make($this->user),
            "media" => MediaResource::collection($this->media),
            "user_parents" => $this->user_parents,
            "closed_at" => $this->closed_at,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }
}
