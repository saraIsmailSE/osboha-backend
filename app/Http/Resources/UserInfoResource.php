<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserInfoResource extends JsonResource
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
            'name' => $this->name . ($this->last_name ? ' ' . $this->last_name : ''),
            'last_name' => $this->last_name,
            'gender' => $this->gender,
            'allowed_to_eligible' => $this->allowed_to_eligible,
            'profile' => new ProfilePictureResource($this->userProfile),
            'roles' => $this->getRoleNames(),
            'gender' => $this->gender,
            'userBooks' => UserBookResource::collection($this->whenLoaded('userBooks')),
        ];
    }
}
