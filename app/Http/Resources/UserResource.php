<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'profile' => new UserProfileResource($this->whenLoaded('userProfile')),
            'is_excluded' => $this->is_excluded,
            'is_hold' => $this->is_hold,
            'is_blocked'=>$this->is_blocked,
            'roles' => $this->getRoleNames(),
            'gender'=>$this->gender
        ];
    }
}
