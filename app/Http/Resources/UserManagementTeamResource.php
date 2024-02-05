<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserManagementTeamResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        //if the user has an admin role, return group named (الإدارة العليا)
        if ($this->hasAnyRole(['admin'])) {
            $group = $this->groups()->where('name', 'الإدارة العليا')->first();
        } else {
            //get the group where the user is a n ambassador
            $group = $this->groups()->wherePivot('user_type', 'ambassador')->first();
        }

        return [
            "parent" => UserInfoResource::make($this->parent),
            "group" => [
                "id" => $group->id,
                "name" => $group->name,
            ],
        ];
    }
}
