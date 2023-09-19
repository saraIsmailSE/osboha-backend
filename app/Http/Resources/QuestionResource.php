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
            "assignee" => UserInfoResource::make($this->assignee),
            "answers" => count($this->answers) > 0 ? AnswerResource::collection($this->answers) : [],
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }
}
