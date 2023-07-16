<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\File;

class MessageFileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $path = $path = public_path("assets/images/") . $this->media;

        return [
            "name" => File::name($path),
            "size" => File::size($path),
            "type" => File::extension($path),
            "audio" => in_array(File::extension($path), ["mp3", "wav", "ogg"]),
            "duration" => $this->duration,
            "url" => asset("assets/images/" . $this->media),
            "preview" => asset("assets/images/" . $this->media),
            // "progress" => 100,
        ];
    }
}
