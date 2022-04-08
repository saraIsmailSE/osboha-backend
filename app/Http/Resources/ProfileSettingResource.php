<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProfileSettingResource extends JsonResource
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
            "posts"=> $this->posts,
            "media"=> $this->media,
            "certificates"=> $this->certificates,
            "infographics"=> $this->infographics,
            "articles"=> $this->articles,
            "thesis"=> $this->thesis,
            "books"=> $this->books,
            "marks"=> $this->marks,
        ];
    }
}
