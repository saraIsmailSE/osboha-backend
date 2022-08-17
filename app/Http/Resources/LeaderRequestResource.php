<?php

namespace App\Http\Resources;
//use App\Http\Resources\UserResource;

use Illuminate\Http\Resources\Json\JsonResource;

class LeaderRequestResource extends JsonResource
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
            'members_num'=> $this->members_num,
            'gender' => $this->gender,
            //"leader_id" =>new UserResource($this->leader_id),
            'current_team_count' => $this->current_team_count,
            'is_done' => $this->is_done, 
        ];
    }
}
