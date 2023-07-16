<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
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
            "_id" => $this->id,
            "indexId" => $this->created_at,
            "content" => $this->body,
            "senderId" => (string) $this->sender->id,
            "username" => $this->sender->name,
            // "avatar" => $this->sender->userProfile->profile_picture ? asset("assets/images/" . $this->sender->userProfile->profile_picture) : null,
            "avatar" =>  asset("assets/images/" . $this->sender->userProfile->profile_picture),
            //format: 13 November
            "date" => $this->created_at->format('d F'),
            //format: 10:20
            "timestamp" => $this->created_at->format('h:i A'),
            "seen" => $this->status == 1 ? true : false,
            "distributed" => true,
            "files" => $this->media()->count() > 0 ? MessageFileResource::collection($this->media) : null,
            "replyMessage" => $this->message ? new MessageResource($this->message) : null,
        ];
    }
}
