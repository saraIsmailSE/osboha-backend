<?php

namespace App\Http\Resources;
//use App\Http\Resources\UserResource;

use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
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

            //'user_id' => new UserProfileResource($this->user_id),
            //'user_id' => new UserProfileResource($this->whenLoaded('user', $this->user_id))
            //"user"=> new UserProfileResource($this->whenLoaded('user')),
            'id' => $this->id,
            'user' => new UserResource($this->whenLoaded('user')),
            'body' => $this->body,
            'type_id' => $this->type_id,
            'allow_comments' => $this->allow_comments,
            // 'tags' => UserInfoResource::collection(unserialize($this->tags)),
            'tags' => unserialize($this->tags),
            'is_approved' => $this->is_approved,
            'is_pinned' => $this->is_pinned,
            'timeline_id' => $this->timeline_id,
            'comments' =>  CommentResource::collection($this->comments),
            'media' => MediaResource::collection($this->media),
        ];
    }
}