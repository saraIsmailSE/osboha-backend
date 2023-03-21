<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserExceptionResource extends JsonResource
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
            'reason' => $this->reason,
            'end_at' => $this->end_at,
            'status'=> $this->status,
            'note' => $this->note,
            'type' => $this->Type->type,
            //'week_id' =>    new WeekResource($this->Week),
            'user' =>    new UserResource($this->User),
            'reviewer' =>    new UserResource($this->reviewer)
        ];
    }
}
