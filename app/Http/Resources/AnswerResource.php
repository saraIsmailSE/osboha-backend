<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AnswerResource extends JsonResource
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
            "answer" => $this->answer,
            "question_id" => $this->question_id,
            "user" =>  UserInfoResource::make($this->user),
            "media" => MediaResource::collection($this->media),
            "created_at" => $this->created_at,
        ];
    }
}
