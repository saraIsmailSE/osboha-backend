<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InfographicResource extends JsonResource
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
            'title' => $this->title,
            //'designer' => new UserResource($this->whenLoaded('user')),
            //'section' => $this->section,
            'series_id' => $this->whenLoaded('series', $this->series_id),
        ];
    }
}