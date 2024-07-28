<?php

namespace App\Traits;

use App\Http\Controllers\Api\NotificationController;
use App\Models\AmbassadorsRequests;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\UserParent;
use App\Notifications\MailAmbassadorDistribution;
use App\Notifications\MailAmbassadorDistributionToYourTeam;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\SignupTrait;
use App\Traits\PathTrait;

trait AmbassadorsTrait
{
    use SignupTrait, PathTrait;

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
            case 'last_month':
                return [
                    Carbon::now()->subMonth()->startOfMonth(),
                    Carbon::now()->subMonth()->endOfMonth()
                ];
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
            ->whereNull('parent_id')
            ->whereBetween('updated_at', $dates)
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
        $leader = UserGroup::where('group_id', $request->group->id)
            ->whereIn('user_type', ['leader', 'special_care_leader'])
            ->whereNull('termination_reason')
            ->first();

        if ($ambassadors_gender == 'any') {
            $ambassador_condition = ['female', 'male'];
        } else {
            $ambassador_condition = [$ambassadors_gender];
        }

        $ambassadors = User::whereIn('gender', $ambassador_condition)
            ->where(function ($query) use ($leader_gender) {
                $query->where('leader_gender', $leader_gender)
                    ->orWhere('leader_gender', 'any');
            })
            ->whereNull('parent_id')
            ->whereNull('request_id')
            ->whereNotNull('email_verified_at')
            ->limit($request->members_num)
            ->get();

        $ambassadorsCount = $ambassadors->count();

        foreach ($ambassadors as $ambassador) {
            $ambassador->request_id = $request->id;
            $ambassador->parent_id = $leader->user->id;
            $ambassador->is_excluded = 0;
            $ambassador->is_hold = 0;
            $ambassador->created_at = Carbon::now();
            $ambassador->save();

            UserParent::create([
                'user_id' => $ambassador->id,
                'parent_id' => $leader->user->id,
                'is_active' => 1,
            ]);
            //create user group record
            UserGroup::updateOrCreate(
                [
                    'user_type' => 'ambassador',
                    'group_id' => $request->group->id,
                    'user_id' => $ambassador->id,
                    'termination_reason' => null
                ],
                [
                    'user_type' => 'ambassador',
                    'group_id' => $request->group->id,
                    'user_id' => $ambassador->id,
                    'termination_reason' => null
                ]
            );

            $ambassador->notify(new MailAmbassadorDistribution($request->group->id));
        }

        // Check if request is done
        $is_done = $this->checkIsDone($request->id);
        if ($is_done) {
            $leaderToNotify = User::find($leader->user->id);
            $leaderToNotify->notify(new MailAmbassadorDistributionToYourTeam($request->group->id));
        }

        if($ambassadorsCount > 0){
            $msg = "تم توزيع سفراء للمجموعة: " . $request->group->name;
            (new NotificationController)->sendNotification($leader->user->id, $msg, ROLES, $this->getGroupPath($request->group->id));
        }
    }
}
