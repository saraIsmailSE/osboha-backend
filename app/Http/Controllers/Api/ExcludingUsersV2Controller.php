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
use App\Traits\PathTrait;

class ExcludingUsersV2Controller extends Controller
{
    use  PathTrait;


    public function excludeUsers()
    {
        try {

            Log::channel('newWeek')->info("Start Users Excluding");
            DB::beginTransaction();
            // Previous Week [0], Before the Previous [1] and Before Before the Previous [3]
            $lastWeekIds = $this->get_last_weeks_ids();
            //current Week
            $currentWeek = Week::where('is_vacation', 0)->latest('id')->first();

            //Case 1: two consecutive zeros
            $this->case1($lastWeekIds, $currentWeek);

            //Case 2: zero - freezed - zero
            $this->case2($lastWeekIds, $currentWeek);
            DB::commit();
            Log::channel('newWeek')->info("Users Excluding Done Successfully");
        } catch (\Exception $e) {
            Log::channel('newWeek')->info($e);
            DB::rollBack();
        }
    }


    private function get_last_weeks_ids($limit = 3)
    {
        //get last weeks without vacations from the data (skip the current week)
        return Week::where('is_vacation', 0)->latest('id')
            ->skip(1)->take($limit)->get();
    }


    //Case 1: two consecutive zeros
    public function case1($lastWeekIds, $currentWeek)
    {
        try {
            $users = User::leftJoin('marks as m1', function ($join) use ($lastWeekIds) {
                $join->on('users.id', '=', 'm1.user_id')
                    ->where('m1.week_id', $lastWeekIds[1]->id);
            })
                ->leftJoin('marks as m2', function ($join) use ($lastWeekIds) {
                    $join->on('users.id', '=', 'm2.user_id')
                        ->where('m2.week_id', $lastWeekIds[0]->id);
                })
                ->leftJoin('marks as m3', function ($join) use ($currentWeek) {
                    $join->on('users.id', '=', 'm3.user_id')
                        ->where('m3.week_id', $currentWeek->id);
                })
                ->whereRaw('COALESCE(m1.reading_mark, 0) = 0')
                ->whereRaw('COALESCE(m1.is_freezed, 0) = 0')
                ->whereRaw('COALESCE(m2.reading_mark, 0) = 0')
                ->whereRaw('COALESCE(m2.is_freezed, 0) = 0')
                ->whereNull('m3.id')
                ->where('users.created_at', '<', $lastWeekIds[2]->created_at)
                ->whereNotNull('users.email_verified_at')
                ->where('users.is_excluded', 0)
                ->get();

            if ($users->isEmpty()) {
                foreach ($users as $user) {
                    if ($user->hasAnyRole(['leader', 'supervisor', 'advisor', 'consultant', "admin"])) {
                        (new NotificationController)->sendNotification(
                            $user->parent_id,
                            'لقد حصل '  . $user->name . ' على صفرين متتالين, يرجى تنبيهه',
                            EXCLUDED_USER,
                            $this->getProfilePath($user->id)
                        );
                    } else {
                        $this->excludeUser($user);
                    }
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::channel('newWeek')->info("CASE1: ".$e);
            DB::rollBack();
        }
    }


    //Case 2: zero - freezed - zero
    public function case2($lastWeekIds, $currentWeek)
    {
        try {
            $users = User::leftJoin('marks as m1', function ($join) use ($lastWeekIds) {
                $join->on('users.id', '=', 'm1.user_id')
                    ->where('m1.week_id', $lastWeekIds[2]->id);
            })
                ->leftJoin('marks as m2', function ($join) use ($lastWeekIds) {
                    $join->on('users.id', '=', 'm2.user_id')
                        ->where('m2.week_id', $lastWeekIds[1]->id);
                })
                ->leftJoin('marks as m3', function ($join) use ($lastWeekIds) {
                    $join->on('users.id', '=', 'm3.user_id')
                        ->where('m3.week_id', $lastWeekIds[0]->id);
                })
                ->leftJoin('marks as m4', function ($join) use ($currentWeek) {
                    $join->on('users.id', '=', 'm4.user_id')
                        ->where('m4.week_id', $currentWeek);
                })
                ->whereRaw('COALESCE(m1.reading_mark, 0) = 0')
                ->whereRaw('COALESCE(m1.is_freezed, 0) = 0')
                ->whereRaw('COALESCE(m2.reading_mark, 0) = 0')
                ->whereRaw('COALESCE(m2.is_freezed, 0) = 0')
                ->whereRaw('COALESCE(m3.reading_mark, 0) = 0')
                ->whereRaw('COALESCE(m3.is_freezed, 0) = 0')
                ->whereNull('m4.id')
                ->where('users.created_at', '<', '2024-01-07 02:59:59')
                ->whereNotNull('users.email_verified_at')
                ->where('users.is_excluded', 0)
                ->whereExists(function ($query) use ($lastWeekIds) {
                    $query->select(DB::raw(1))
                        ->from('marks as m5')
                        ->whereRaw('m5.user_id = users.id')
                        ->where('m5.week_id', $lastWeekIds[1]->id)
                        ->where('m5.is_freezed', 1);
                })
                ->get();
            if ($users->isEmpty()) {
                foreach ($users as $user) {
                    if ($user->hasAnyRole(['leader', 'supervisor', 'advisor', 'consultant', "admin"])) {
                        (new NotificationController)->sendNotification(
                            $user->parent_id,
                            'لقد حصل '  . $user->name . ' على صفر - تجميد - صفر , يرجى تنبيهه',
                            EXCLUDED_USER,
                            $this->getProfilePath($user->id)
                        );
                    } else {
                        $this->excludeUser($user);
                    }
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::channel('newWeek')->info("CASE2: ".$e);
            DB::rollBack();
        }
    }

    public function excludeUser($user)
    {
        $userGroup = UserGroup::where('user_id', $user->id)->where('user_type', 'ambassador')->whereNull('termination_reason')->first();
        if ($userGroup) {
            $userGroup->termination_reason = 'excluded';
            $userGroup->save();
        }

        $msg = 'لقد تم استبعاد السفير ' . $user->name . ' من الفريق بسبب عدم التزامه بالقراءة طيلة الأسابيع الماضية';
        (new NotificationController)->sendNotification($user->parent_id, $msg, EXCLUDED_USER);

        //update the user parent_id to null and exclude the user
        $userGroup->user->parent_id = null;
        $userGroup->user->is_excluded = 1;
        $userGroup->user->save();
        return true;
    }
}
