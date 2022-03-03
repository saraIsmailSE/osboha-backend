<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return[
            "rate"=> $this->rate,
            // 'user'=> new UserResource($this->whenLoaded('user')),
            // 'post'=> new PostResource($this->whenLoaded('post')),
            // 'comment'=> new CommentResource($this->whenLoaded('comment'))

        ];
    }
}
