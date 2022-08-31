<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GroupResource extends JsonResource
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
            'name'=> $this->name,
            'description'=> $this->description,
            'type'=> $this->TypeName->type,
          //  'image'=> new MediaResource($this->media)
            'creator_id'=> $this->creator_id,
            'members' => $this->user
        ];
    }
}
