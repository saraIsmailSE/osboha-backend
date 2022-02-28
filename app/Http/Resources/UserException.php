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
<<<<<<< HEAD
      // 'week_id' =>    new WeekResource($this->Week),
=======
        // 'week_id' =>    new WeekResource($this->Week),
>>>>>>> d7e770301d78e18b70af47e1491e06b985916a6d
       // 'user_id' =>    new UserResource($this->User),
        
        ];
    }
}
