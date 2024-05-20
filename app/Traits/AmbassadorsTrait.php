<?php

namespace App\Traits;

use App\Models\AmbassadorsRequests;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait AmbassadorsTrait
{

    public function getStatistics($timeFrame)
    {
        $dates = $this->getDateRange($timeFrame);

        return [
            'uncompleted_requests' => $this->getUncompletedRequests($dates),
            'completed_requests' => $this->getCompletedRequests($dates),
            'requests_female' => $this->getRequestsByGender('female', $dates),
            'requests_male' => $this->getRequestsByGender('male', $dates),
            'requests_any' => $this->getRequestsByGender('any', $dates),
            'new_allocated_users' => $this->getNewAllocatedUsers($dates),
            'not_allocated_users' => $this->getNotAllocatedUsers($dates),
            'users_not_complete_registration' => $this->getUsersNotCompleteRegistration($dates),
        ];
    }

    private function getDateRange($timeFrame)
    {
        switch ($timeFrame) {
            case 'today':
                return [Carbon::today(), Carbon::tomorrow()];
            case 'yesterday':
                return [Carbon::yesterday(), Carbon::today()];
            case 'week':
                return [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()];
            case 'month':
                return [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()];
            default:
                throw new \Exception("Invalid time frame: $timeFrame");
        }
    }

    private function getUncompletedRequests($dates)
    {
        return DB::table('ambassadors_requests')
            ->where('is_done', 0)
            ->count();
    }

    private function getCompletedRequests($dates)
    {
        return DB::table('ambassadors_requests')
            ->where('is_done', 1)
            ->count();
    }

    private function getRequestsByGender($gender, $dates)
    {
        return DB::table('ambassadors_requests')
            ->where('ambassadors_gender', $gender)
            ->where('is_done', 0)
            ->count();
    }

    private function getNewAllocatedUsers($dates)
    {
        return DB::table('users')
            ->whereNotNull('request_id')
            ->where('request_id', '!=', 0)
            ->whereBetween('created_at', $dates)
            ->count();
    }

    private function getNotAllocatedUsers($dates)
    {
        return DB::table('users')
            ->whereNotNull('leader_gender')
            ->whereNotNull('email_verified_at')
            ->whereNull('request_id')
            ->whereBetween('created_at', $dates)
            ->count();
    }

    private function getUsersNotCompleteRegistration($dates)
    {
        return DB::table('users')
            ->whereNull('email_verified_at')
            ->whereBetween('created_at', $dates)
            ->count();
    }
    public function distributeAmbassadors($requestID)
    {
        $request = AmbassadorsRequests::find($requestID);
        $leader_gender = $request->leader_gender;
        $ambassadors_gender = $request->ambassadors_gender;

        $ambassadors = DB::table('users')
            ->where(function ($query) use ($ambassadors_gender) {
                $query->where('gender', $ambassadors_gender)
                    ->orWhere('gender', 'any');
            })
            ->where(function ($query) use ($leader_gender) {
                $query->where('leader_gender', $leader_gender)
                    ->orWhere('leader_gender', 'any');
            })
            ->whereNull('request_id')
            ->whereNotNull('email_verified_at')
            ->limit($request->members_num);

        foreach ($ambassadors as $ambassador) {
            $ambassador->request_id;
            $ambassador->save();
        }
    }
}
