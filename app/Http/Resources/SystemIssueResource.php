<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SystemIssueResource extends JsonResource
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
            "reporter_description"=> $this->reporter_description,
            "reviewer_note"=> $this->reviewer_note,
            "solved"=> $this->solved,
            //"reporter"=> new UserResource($this->whenLoaded('user')),
            //"reviewer"=> new UserResource($this->whenLoaded('user')), //Maybe need to add relationship for reviewer??

        ];
    }
}
