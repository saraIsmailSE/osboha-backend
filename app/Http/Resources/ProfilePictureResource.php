<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProfilePictureResource extends JsonResource
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
            'profile_picture' => $this->profile_picture, // ?  asset('assets/images/' . $this->profile_picture) : null,
            'cover_picture' => $this->cover_picture, // ? asset('assets/images/' . $this->cover_picture) : null,
        ];
    }
}