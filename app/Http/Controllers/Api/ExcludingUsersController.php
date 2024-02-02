<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mark;
use App\Models\User;
use App\Models\UserException;
use App\Models\UserGroup;
use App\Models\Week;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExcludingUsersController extends Controller
{
    protected $lastWeekIds = [];
    protected $excludedUsers = [];

    public function excludeUsers()
    {
        Log::channel('newWeek')->info("Start Users Excluding");
        $this->excludedUsers = [];
        //get last three weeks ids
        $this->lastWeekIds = $this->get_last_weeks_ids();
        // dd($this->lastWeekIds);

        DB::beginTransaction();
        try {
            $this->check_all_users();

            DB::commit();

            $this->notifyExcludedUsers();

            Log::channel('newWeek')->info("Users Excluding Done Successfully");
        } catch (\Exception $e) {
            Log::channel('newWeek')->info($e);
            Log::error($e);

            DB::rollBack();
            return $this->jsonResponseWithoutMessage($e->getMessage() . ' at line ' . $e->getLine(), 'data', 500);
        }
    }

    /**
     * check which user is excluded and which is not
     * @author Asmaa
     * get all the users except the excluded and hold users
     * chunk the data to begin checking on those who are excluded
     * update the excluded users
     * @uses update_excluded_user()
     * @return True if the marks and updating exculded members are done correctly,
     * @throws Exception error if anything wrong happens
     */

    private function check_all_users()
    {
        //get all the users and update their records if they are excluded (just ambassdors0)
        $all_users = User::where('is_excluded', 0)->where('is_hold', 0)
            ->where('parent_id', '!=', null)
            ->orderBy('id')
            ->chunkByID(100, function ($users) {
                try {
                    //to avoid executing an updated Statement for every single user in our DB
                    DB::beginTransaction();

                    foreach ($users as $user) {
                        //update excluded member then insert mark
                        $this->update_excluded_user($user);
                    }

                    DB::commit();
                } catch (\Exception $exception) {
                    Log::channel('newWeek')->info($exception);
                    DB::rollBack();
                    throw $exception;
                }
            }, 'users.id', 'id');

        return $all_users;
    }

    /**
     * update excluded user then add mark
     * @author Asmaa
     * 1- check exam exception for user and update status if finished
     * 2- if there are less than 2 weeks in the system then insert mark for single user without checking for excluded users
     * 3- if there are more than 2 weeks in the system
     * 3.1- get last week marks for the user
     * 3.2- check if the user excluded (two consecutive zeros, zero - freezed - zero)
     * 3.3- if the user is excluded then update the status of the user to excluded
     * 3.4- if the user is not excluded then insert mark for single user
     * @param Object $user
     * @uses check_exams_exception_for_user()
     * @uses UpdateUserStats Event
     * @return True if the updating excluded member or inserting new mark record is done correctly,
     * @return Null if anything wrong happens
     *
     */
    private function update_excluded_user($user)
    {
        $marks = Mark::where('user_id', $user->id)
            ->whereIn('week_id', $this->lastWeekIds)
            ->orderBy('week_id', 'desc')
            ->get();

        $is_excluded = $this->checkExcludedUserFromMarks($marks, $user->id);
        //if the user does not satisfy the below cases so he/she is not excluded then insert a record for him/her
        if (!$is_excluded) {
            //check mark for super roles
            $markOfLastWeek = $marks->count() > 0 ? ($marks->first()->reading_mark + $marks->first()->writing_mark + $marks->first()->support) : 0;
            $this->checkFullMarkForSuperRoles($user, $markOfLastWeek);
        } else {
            $old_user = $user->getOriginal();

            //check if the excluded user is a super role, if yes then do not exclude him/her
            if ($this->checkSuperRolesForExcluding($user)) {
                return $user;
            }

            array_push($this->excludedUsers, $user->id);

            return $user;
        }
    }

    /**
     * get the last three weeks without vacations from the system
     * @author Asmaa
     * @param int $limit = 3
     * @return array of week_ids of last three weeks,
     * @return Null if no weeks were found
     */
    private function get_last_weeks_ids($limit = 3)
    {
        //get last weeks without vacations from the data (skip the current week)
        return Week::where('is_vacation', 0)->latest('id')
            ->skip(1)->take($limit)->pluck('id')->toArray();
    }


    /**
     * Check if the user is excluded based on their previous marks
     * return true if the user gets 2 consecutive zeros of last 2 weeks with no freezing
     * return true if the user gets zero - freezed - zero of last 3 weeks
     * @param $marks
     * @return bool
     */
    private function checkExcludedUserFromMarks($marks, $user_id)
    {
        $currentWeek = Week::where('is_vacation', 0)->latest('id')->first();
        $currentMark = Mark::where('user_id', $user_id)->where('week_id', $currentWeek->id)->first();

        $marksWeekIds = $marks->pluck('week_id')->toArray();
        $markIndex_1 = count($this->lastWeekIds) > 0 ? array_search($this->lastWeekIds[0], $marksWeekIds) : null;
        $mark_last_week = !is_null($markIndex_1) ? $markIndex_1 !== FALSE && $marks[$markIndex_1]->reading_mark > 0 : null;
        $last_week_freezed = count($this->lastWeekIds) > 0 ? $this->checkFreezedInWeek($this->lastWeekIds[0], $user_id) : null;

        $markIndex_2 = count($this->lastWeekIds) > 1 ? array_search($this->lastWeekIds[1], $marksWeekIds) : null;
        $mark_second_last_week = !is_null($markIndex_2) ? $markIndex_2 !== FALSE && $marks[$markIndex_2]->reading_mark > 0 : null;
        $second_last_week_freezed = count($this->lastWeekIds) > 1 ? $this->checkFreezedInWeek($this->lastWeekIds[1], $user_id) : null;

        $markIndex_3 = count($this->lastWeekIds) > 2 ? array_search($this->lastWeekIds[2], $marksWeekIds) : null;
        $mark_third_last_week = !is_null($markIndex_3) ? $markIndex_3 !== FALSE && $marks[$markIndex_3]->reading_mark > 0 : null;
        $mark_third_last_week_freezed = count($this->lastWeekIds) > 2 ? $this->checkFreezedInWeek($this->lastWeekIds[2], $user_id) : null;

        $is_excluded = false;

        //if there is a mark in the current week, skip excluding
        if ($currentMark !== null) {
            return $is_excluded;
        }

        //2 consecutive zeros of last 2 weeks or zero - freezed - zero => excluded
        if ($mark_last_week === FALSE && $mark_second_last_week === FALSE) {
            // dd('2 consecutive zeros of last 2 weeks');
            //check if freezed or not
            if (
                $last_week_freezed === FALSE && $second_last_week_freezed === FALSE
            ) {
                // dd('excluded');
                $is_excluded = true;
            }
        } else if (
            $mark_last_week === FALSE &&
            $second_last_week_freezed &&
            $mark_third_last_week === FALSE
        ) {
            //check if freezed or not
            if ($last_week_freezed === FALSE && $mark_third_last_week_freezed === FALSE) {
                $is_excluded = true;
            }
        }

        return $is_excluded;
    }


    /**
     * check if the user is freezed in a week or not
     * @author Asmaa
     * @param Int $user_id
     * @param Int $week_id
     * @return True if the user is freezed in that week
     * @return False if the user is not freezed in that week
     */
    private function checkFreezedInWeek($week_id, $user_id)
    {
        $week = Week::find($week_id);
        $week_startAt = Carbon::parse($week->created_at)->format('Y-m-d');
        $week_endAt = Carbon::parse($week->main_timer)->format('Y-m-d');

        $user_exception = UserException::where('user_id', $user_id)
            ->whereIn('status', [config('constants.ACCEPTED_STATUS'), config('constants.FINISHED_STATUS')])
            ->with('type', function ($query) {
                $query->where('type', config('constants.FREEZE_THIS_WEEK_TYPE'))
                    ->orWhere('type', config('constants.FREEZE_NEXT_WEEK_TYPE'))
                    ->orWhere('type', config('constants.EXCEPTIONAL_FREEZING_TYPE'));
            })
            ->whereDate('start_at', '>=', $week_startAt)
            ->whereDate('end_at', '<=', $week_endAt)
            ->latest('id')
            ->first();

        return $user_exception === null ? FALSE : TRUE;
    }

    /**
     * Check if user is (leader - supervisor - advisor - consultant) to not exclude it and sent a warning to his parent
     * @param User $user
     * @return bool
     */
    private function checkSuperRolesForExcluding($user)
    {
        if ($user->hasAnyRole(['leader', 'supervisor', 'advisor', 'consultant', "admin"])) {
            $lastRole = $user->roles->first();
            $arabicRole = config('constants.ARABIC_ROLES')[$lastRole->name];
            (new NotificationController)->sendNotification(
                $user->parent_id,
                'لقد حصل ال' . $arabicRole . ' ' . $user->name . ' على صفرين متتالين, يرجى تنبيهه',
                EXCLUDED_USER,
                $this->getProfilePath($user->id)
            );
            return true;
        }
        return false;
    }

    /**
     * check if user is (leader - supervisor - advisor - consultant) and did not get full mark to send a warning to his parent
     * @param User $user
     * @param Int mark
     * @return void
     */
    private function checkFullMarkForSuperRoles($user, $mark)
    {
        if ($user->hasAnyRole(['leader', 'supervisor', 'advisor', 'consultant']) && $mark < 100) {
            $lastRole = $user->roles->first();
            $arabicRole = config('constants.ARABIC_ROLES')[$lastRole->name];

            (new NotificationController)->sendNotification(
                $user->parent_id,
                'لقد حصل ال' . $arabicRole . ' ' . $user->name . ' على ' . $mark . ' من 100, يرجى تنبيهه',
                MARKS,
                $this->getProfilePath($user->id)
            );
        }
    }

    /**
     * Notify Excluded users and their leaders that they are excluded
     * @return JsonResponse
     */
    private function notifyExcludedUsers()
    {
        if (count($this->excludedUsers) == 0) {
            return;
        }

        $notification = new NotificationController();

        try {
            $users = User::whereIn('id', $this->excludedUsers)->get();
            $userGroups = UserGroup::whereIn('user_id', $this->excludedUsers)->where('user_type', 'ambassador')->get();

            try {
                DB::beginTransaction();
                foreach ($userGroups as $userGroup) {
                    $userGroup->termination_reason = 'excluded';
                    $userGroup->save();

                    $msg = 'لقد تم استبعاد السفير ' . $userGroup->user->name . ' من الفريق بسبب عدم التزامه بالقراءة طيلة الأسابيع الماضية';
                    $notification->sendNotification($userGroup->user->parent_id, $msg, EXCLUDED_USER);

                    //update the user parent_id to null and exclude the user
                    $userGroup->user->parent_id = null;
                    $userGroup->user->is_excluded = 1;
                    $userGroup->user->save();
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
