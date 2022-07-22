<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PollVoteResource extends JsonResource
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
            //'post_id' => new PostResource($this->post_id),
            //'user_id' => new UserResource($this->user_id),

            //"user_id"=> $this->user,
            "user_id"=> $this->user_id,
            "post_id"=> $this->post_id,
            'option' => unserialize($this->option)
        ];
    }
}
