<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\User;

class RoomResource extends JsonResource
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
            "creator" => User::find($this->creator_id),
            "participant" => $this-> participant,
            "name" => $this->name,
            "type" => $this->type,
            "messages status" => $this->messages_status
        ];
    }
}
