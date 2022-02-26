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
        'reason' => $this->reason,
        'duration' => $this->duration,
        'status'=> $this->start_at,
        'leader_note' => $this->leader_note,
        'advisor_note'=> $this->advisor_note,
      // 'week_id' =>    new WeekResource($this->Week),
       // 'user_id' =>    new UserResource($this->User),
        
        ];
    }
}
