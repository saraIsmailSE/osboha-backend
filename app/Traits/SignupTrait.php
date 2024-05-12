<?php

namespace App\Traits;

use App\Models\AmbassadorsRequests;
use Illuminate\Support\Facades\Log;

trait SignupTrait
{

    // select team
    public function selectTeam($ambassador, $leaderGender)
    {
        //NEED $ambassador, $leaderGender
        //ambassador is a User instance

        $ambassadorGender = $ambassador->gender;

        if ($ambassadorGender == 'any') {
            $ambassador_condition = "ambassadors_gender = '" . $ambassadorGender . "'";
        } else {
            $ambassador_condition = "(ambassadors_gender = '" . $ambassadorGender . "' OR ambassadors_gender = 'any')";
        }

        if ($leaderGender == "any") {
            $leader_condition = " (leader_gender IN ('female', 'male'))";
        } else {
            $leader_condition = "leader_gender = '" . $leaderGender . "'";
        }

        //Select High Priority

        $result = AmbassadorsRequests::with('group')
            ->whereRaw($leader_condition)
            ->whereRaw($ambassador_condition)
            ->where('is_done', 0)
            ->where('high_priority', 1)
            ->orderBy('created_at', 'asc')
            ->first();

        if ($result) {
            return $result;
        }

        //Select Special Care
        $result = AmbassadorsRequests::with(['group' => function ($query) {
            $query->whereHas('type', function ($q) {
                $q->where('type', '=', 'special_care');
            });
        }])
            ->whereRaw($leader_condition)
            ->whereRaw($ambassador_condition)
            ->where('is_done', '=', 0)
            ->orderBy('created_at', 'asc')
            ->first();

        if ($result) {
            return $result;
        }

        //Select new teams
        $result = AmbassadorsRequests::with(['group' => function ($query) {
            $query->whereHas('userAmbassador')
                ->withCount(['userAmbassador as ambassador_count'])
                ->having('ambassador_count', '=', 0);
        }])->whereRaw($leader_condition)
            ->whereRaw($ambassador_condition)
            ->where('is_done', 0)
            ->orderBy('created_at', 'asc')
            ->first();

        if ($result) {
            return $result;
        }

        //Select teams with members count between 1 and 12
        $result = AmbassadorsRequests::with(['group' => function ($query) {
            $query->whereHas('userAmbassador')
                ->withCount(['userAmbassador as ambassador_count'])
                ->having('ambassador_count', '>=', 1)
                ->having('ambassador_count', '<=', 12);
        }])
            ->whereRaw($leader_condition)
            ->whereRaw($ambassador_condition)
            ->where('is_done', 0)
            ->orderBy('created_at', 'asc')
            ->first();

        if ($result) {
            return $result;
        }

        //Select teams with members count > 12
        $result = AmbassadorsRequests::with(['group' => function ($query) {
            $query->whereHas('userAmbassador')
                ->withCount(['userAmbassador as ambassador_count'])
                ->having('ambassador_count', '>', 12);
        }])->whereRaw($leader_condition)
            ->whereRaw($ambassador_condition)
            ->where('is_done', 0)
            ->orderBy('created_at', 'asc')
            ->first();

        if ($result) {
            return $result;
        }

        return null;
    }

    public function checkIsDone($request_id)
    {
        $ambassadorsRequest = AmbassadorsRequests::withCount('ambassadors')->find($request_id);
        if ($ambassadorsRequest) {

            if ($ambassadorsRequest->ambassadors_count >= $ambassadorsRequest->members_num) {
                $ambassadorsRequest->is_done = 1;
                $ambassadorsRequest->save();
                return true;
            }
            return false;
        }
    }
}
