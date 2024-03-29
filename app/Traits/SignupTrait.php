<?php

namespace App\Traits;


trait SignupTrait
{

    //check if new user is already an ambassador
    public function checkAmbassador()
    {
    }

    // select team
    public function selectTeam($ambassador, $leaderGender)
    {
        $ambassadorGender = $ambassador->gender;
        if ($ambassadorGender == 'any') {
            $ambassador_condition = "leader_request.gender = '" . $ambassadorGender . "'";
        } else {
            $ambassador_condition = "(leader_request.gender = '" . $ambassadorGender . "' OR leader_request.gender = 'any')";
        }

        if ($leaderGender == "any") {
            $leader_condition = " (leader_info.leader_gender = 'female' OR leader_info.leader_gender = 'male')";
        } else {
            $leader_condition = "leader_info.leader_gender = '" . $leaderGender . "'";
        }
    }
}
