<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\User;

class ModifiedThesesResource extends JsonResource
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
            "user" => $this->whenLoaded('user'),
            "week" => $this->whenLoaded('week'),
            "thesis" => $this->whenLoaded('thesis'),
            "modifier" => $this->whenLoaded('modifier'),
            "modifierReason" => $this->modifier_reason,
            "headModifier" => $this->whenLoaded('headModifier'),
            "headModifierReason" => $this->head_modifier_reason,
            'status' => $this->status,
        ];
    }
}