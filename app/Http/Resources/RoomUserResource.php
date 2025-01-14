<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RoomUserResource extends JsonResource
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
            "_id" => $this->id,
            "username" => $this->name . ($this->last_name ? " " . $this->last_name : ""),
            "avatar" => asset('assets/images/' . $this->userProfile->profile_picture),
        ];
    }
}
