<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        #######ASMAA#######
        
        return
        [
            'title' => $this->title,
            //'post' => new PostResource($this->whenLoaded('post', $this->post_id)),
            //'user' => new UserResource($this->whenLoaded('user', $this->user_id)),
            'section' => $this->section, 
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at, 
        ];
    }
}
