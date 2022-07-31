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
            // 'book_id' => new BookResource($this->whenLoaded('book', $this->book_id)),
            // 'mark_id' => new MarkRsource($this->whenLoaded('mark', $this->mark_id)),
            // 'user_id' => new UserRsource($this->whenLoaded('user', $this->user_id)),
            // 'comment_id' => new CommentRsource($this->whenLoaded('comment', $this->comment_id)),            
            'total_pages' => $this->total_pages,
            'max_length' => $this->max_length,
            'total_screenshots' => $this->total_screenshots,
        ];
    }
}