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
            //'post_id' => new PostResource($this->post_id),
            //'user_id' => new UserResource($this->user_id),
            'section' => $this->section,  
        ];
    }
}
