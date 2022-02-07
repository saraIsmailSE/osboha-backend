<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserException extends JsonResource
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
            'week_id' => $this->week_id,
            'reason' => $this->reason,
            'type' => $this->type,
            'duration' => $this->duration,
            'status' => $this->status,
            'start_at' => $this->start_at,
            'leader_note' => $this->leader_note,
            'advisor_note' => $this->advisor_note
        ];
    }
}
