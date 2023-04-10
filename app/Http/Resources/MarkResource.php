<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MarkResource extends JsonResource
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
            "reading_mark" => $this->reading_mark,
            "writing_mark" => $this->writing_mark,
            "support" => $this->support,
            "total thesis" => $this->total_thesis,
            "total screenshot" => $this->total_screenshot,
            "updated at" => $this->updated_at,
            "thesis" => $this->thesis,
            "is_freezed" => $this->is_freezed,
        ];
    }
}