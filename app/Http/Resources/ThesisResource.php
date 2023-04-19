<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ThesisResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        ###Asmaa##

        return [
            'id' => $this->id,
            'book' => new BookResource($this->whenLoaded('book')),
            'mark' => new MarkRsource($this->whenLoaded('mark')),
            'user' => new UserResource($this->whenLoaded('user')),
            'comment' => new CommentResource($this->whenLoaded('comment')),
            'start_page' => $this->start_page,
            'end_page' => $this->end_page,
            'status' => $this->status,
            'max_length' => $this->max_length,
            'total_screenshots' => $this->total_screenshots,
        ];
    }
}