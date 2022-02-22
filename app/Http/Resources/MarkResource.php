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
            "out of 90" => $this->out_of_90,
            "out of 100" => $this->out_of_100,
            "support" => $this->support, 
            "total thesis" => $this->total_thesis, 
            "total screenshot" => $this->total_screenshot,
            "updated at" => $this->updated_at ? $this->updated_at->format('d-m-Y') : ''
        ];
    }
}
