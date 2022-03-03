<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return[
            "name"=> $this->name,
            "writer"=> $this->writer,
            "publisher"=> $this->publisher,
            "brief"=> $this->brief,
            "start_page"=> $this->start_page,
            "end_page"=> $this->end_page,
            "link"=> $this->link,
            "section"=> $this->section,
            "type"=> $this->type,
            "level"=> $this->level,
           // "posts"=> PostResource::collection($this->whenLoaded('post')),

        ];
    }
}
