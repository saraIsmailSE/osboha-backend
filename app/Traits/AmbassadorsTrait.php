<?php

namespace App\Traits;

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
            ->whereBetween('created_at', $dates)
            ->count();
    }

    private function getNotAllocatedUsers($dates)
    {
        return DB::table('users')
            ->whereNotNull('leader_gender')
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
}
