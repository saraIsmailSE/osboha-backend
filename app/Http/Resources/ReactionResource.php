<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
class ReactionResource extends JsonResource
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
            // 'media'=> new MediaResource($this->whenLoaded('media')),
            // 'user'=> new UserResource($this->whenLoaded('user')),
            // 'post'=> new PostResource($this->whenLoaded('post')),
             // 'comment'=>new CommentResource($this->whenLoaded('comment'))
            ];
    }
}
