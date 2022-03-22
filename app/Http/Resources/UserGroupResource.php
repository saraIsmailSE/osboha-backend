<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserGroupResource extends JsonResource
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
            // 'user' => new UserResource($this->whenLoaded('user')),
            // 'group' => new Group($this->whenLoaded('group')),
            'user_type' => $this->user_type,
            'termination_reason' => $this->termination_reason,
        ];
    }
}
