<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
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
        
        return [
            'name' => $this->name,
            'version' => $this->version,
            //'post' => new PostResource($this->whenLoaded('post', $this->post_id)),  
            'created_at' => $this->created_at, 
        ];
    }
}
