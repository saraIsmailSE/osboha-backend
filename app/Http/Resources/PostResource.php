<?php

namespace App\Http\Resources;

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
            //'user_id' => new UserResource($this->user_id),
            'type' => $this->resource->type,
            'allow_comments' => $this->resource->allow_comments,
            'tag' => unserialize($this->tag),
            'vote' => unserialize($this->vote),
            'is_approved' => $this->resource->is_approved,
            'is_pinned' => $this->resource->is_pinned,
            'timeline_id' => $this->resource->timeline_id,
         
        ];
    }
}
