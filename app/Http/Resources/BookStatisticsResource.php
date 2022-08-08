<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookStatisticsResource extends JsonResource
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
            'total' => $this->total, 
            'simple' => $this->simple,
            'intermediate' => $this->intermediate,
            'advanced' => $this->advanced,
            'method_books' => $this->method_books,
            'ramadan_books' => $this->ramadan_books,
            'children_books' => $this->children_books,
            'young_people_books' => $this->young_people_books
        ];
    }
}
