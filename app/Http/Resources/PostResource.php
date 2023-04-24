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
            'id' => $this->id,
            'user' => new UserInfoResource($this->whenLoaded('user')),
            'body' => $this->body,
            'allow_comments' => $this->allow_comments,
            'is_approved' => $this->is_approved,
            'is_pinned' => $this->is_pinned,
            'timeline_id' => $this->timeline_id,
            'comments' =>  CommentResource::collection($this->whenLoaded('comments')),
            'media' => MediaResource::collection($this->media),
            'pollOptions' => PollOptionResource::collection($this->pollOptions),
            'taggedUsers' => TaggedUserResource::collection($this->taggedUsers),
            'votes_count' => $this->poll_votes_count ?? 0,
            "comments_count" => $this->comments_count ?? 0,
            'created_at' => $this->created_at,
        ];
    }
}