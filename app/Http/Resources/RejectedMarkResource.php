<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\User;

class RejectedMarkResource extends JsonResource
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
            "user" => $this->user,
            "week" => $this->week,
            "thesis" => $this->thesis,
            "rejecter" => User::find($this->rejecter_id),
            "rejecter note" => $this->rejecter_note,
            "is acceptable" => $this->is_acceptable
        ];
    }
}
