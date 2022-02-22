<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            "hiring_date" => $this->hiring_date,
            "termination_reason" => $this->termination_reason,
            "termination_date" => $this->termination_date,
            // "user" => new UserResource($this->whenLoaded('user')),
            // return role description?
        ];
    }
}
