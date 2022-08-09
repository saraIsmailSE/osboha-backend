<?php

namespace App\Http\Resources;
use App\Http\Resources\LeaderRequestResource;

use Illuminate\Http\Resources\Json\JsonResource;

class HighPriorityRequestResource extends JsonResource
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
            'request_id' => $this->request_id,//new LeaderRequestResource($this->request_id),

        ];
    }
}