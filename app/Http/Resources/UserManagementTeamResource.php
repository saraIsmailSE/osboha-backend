<?php

namespace App\Http\Resources;

use App\Models\GroupType;
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
        $ambassadorTeam = $this->groups()->wherePivot('user_type', 'ambassador')->first();
        $leaderTeam = null;
        $ambassadorTeamTitle = "";
        $leaderTeamTitle = "";

        //if the user has an admin role, return group named (الإدارة العليا)
        if ($this->hasRole('admin')) {
            $groupTypeId = GroupType::where('type', 'Administration')->first()->id;
            $leaderTeam = $this->groups()->where('type_id', $groupTypeId)->wherePivot('user_type', 'admin')->first();

            $ambassadorTeamTitle = "فريق المتابعة";
            $leaderTeamTitle = "الفريق الإداري";
        } else if ($this->hasRole('consultant')) {
            $groupTypeId = GroupType::where('type', 'consultation')->first()->id;
            $leaderTeam = $this->groups()->where('type_id', $groupTypeId)
                ->wherePivot('user_type', 'consultant')->first();

            $ambassadorTeamTitle =  "فريق المتابعة";
            $leaderTeamTitle = "فريق الاستشارة";
        } else if ($this->hasRole('advisor')) {
            $groupTypeId = GroupType::where('type', 'advising')->first()->id;
            $leaderTeam = $this->groups()->where('type_id', $groupTypeId)
                ->wherePivot('user_type', 'advisor')->first();

            $ambassadorTeamTitle =  "فريق المتابعة";
            $leaderTeamTitle = "فريق التوجيه";
        } else if ($this->hasRole('supervisor')) {
            $groupTypeId = GroupType::where('type', 'supervising')->first()->id;
            $leaderTeam = $this->groups()->where('type_id', $groupTypeId)
                ->wherePivot('user_type', 'supervisor')->first();

            $ambassadorTeamTitle = "فريق المتابعة";
            $leaderTeamTitle = "فريق الرقابة";
        } else if ($this->hasRole('leader')) {
            $groupTypeId = GroupType::where('type', 'followup')->first()->id;
            $leaderTeam = $this->groups()->where('type_id', $groupTypeId)
                ->wherePivot('user_type', 'leader')->first();

            $ambassadorTeamTitle = "فريق الرقابة";
            $leaderTeamTitle = "فريق المتابعة";
        } else {
            return [];
        }

        return [
            "parent" => UserInfoResource::make($this->parent),
            "ambassadorTeam" => $ambassadorTeam ?  [
                "id" => $ambassadorTeam->id,
                "name" => $ambassadorTeam->name,
                'title' => $ambassadorTeamTitle
            ] : null,
            "leaderTeam" =>
            $leaderTeam ?
                [
                    "id" => $leaderTeam->id,
                    "name" => $leaderTeam->name,
                    'title' => $leaderTeamTitle
                ] : null,
        ];
    }
}
