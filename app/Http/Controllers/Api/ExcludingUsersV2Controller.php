<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mark;
use App\Models\User;
use App\Models\UserException;
use App\Models\UserGroup;
use App\Models\UserParent;
use App\Models\Week;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\PathTrait;
use Illuminate\Database\Eloquent\Collection;

class ExcludingUsersV2Controller extends Controller
{
    use  PathTrait;


    public function excludeUsers()
    {
        try {

            /**
             * Bug Fix: Ambassadors should not be excluded if they have received a mark in the new week.
             *
             * @issue Reported by Sarah
             * @fix Adjust the exclusion logic to ensure ambassadors are not removed if they have activity in the current week.
             *
             * @scenario
             * - The ambassador received zero in weeks 1 and 2.
             * - They received a mark in week 3.
             * - The current logic excludes them due to receiving zero in weeks 1 and 2.
             * - However, since they received a mark in week 3, they should not be excluded.
             */

            Log::channel('newWeek')->info("Start Users Excluding");
            // Previous Week [0], Before the Previous [1] and Before Before the Previous [3]
            $lastWeekIds = $this->get_last_weeks_ids();
            //current Week
            $currentWeek = Week::where('is_vacation', 0)->latest('id')->first();

            //Case 1: two consecutive zeros
            $this->case1($lastWeekIds, $currentWeek);

            //Case 2: zero - freezed - zero
            $this->case2($lastWeekIds, $currentWeek);
            Log::channel('newWeek')->info("Users Excluding Done Successfully");
        } catch (\Exception $e) {
            Log::channel('newWeek')->info($e);
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
            Log::channel('newWeek')->info("case 1 start");
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
                ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                ->whereRaw('COALESCE(m1.reading_mark, 0) = 0')
                ->whereRaw('COALESCE(m1.is_freezed, 0) = 0')
                ->whereRaw('COALESCE(m2.reading_mark, 0) = 0')
                ->whereRaw('COALESCE(m2.is_freezed, 0) = 0')
                ->whereNull('m3.id')
                ->where('users.created_at', '<', $lastWeekIds[2]->created_at)
                ->whereNotNull('users.email_verified_at')
                ->where('users.is_excluded', 0)
                ->pluck('users.id');
            User::whereIn('id', $users)
                ->chunkById(100, function (Collection $users) {
                    DB::beginTransaction();

                    foreach ($users as $user) {
                        if ($user->hasanyrole('admin|consultant|advisor|supervisor|leader')) {
                            if ($user->parent_id) {
                                (new NotificationController)->sendNotification(
                                    $user->parent_id,
                                    'لقد حصل '  . $user->name . ' على صفرين متتالين, يرجى تنبيهه',
                                    EXCLUDED_USER,
                                    $this->getProfilePath($user->id)
                                );
                            }
                        } else {
                            $this->excludeUser($user);
                        }
                    }
                    DB::commit();
                });

            return true;
        } catch (\Exception $e) {
            Log::channel('newWeek')->info("CASE1: " . $e);
        }
    }


    //Case 2: zero - freezed - zero
    public function case2($lastWeekIds, $currentWeek)
    {
        try {
            Log::channel('newWeek')->info("case 2 start");

            $users = User::with('roles')->leftJoin('marks as m1', function ($join) use ($lastWeekIds) {
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
                ->where('users.created_at', '<', $lastWeekIds[2]->created_at)
                ->whereNotNull('users.email_verified_at')
                ->where('users.is_excluded', 0)
                ->whereExists(function ($query) use ($lastWeekIds) {
                    $query->select(DB::raw(1))
                        ->from('marks as m5')
                        ->whereRaw('m5.user_id = users.id')
                        ->where('m5.week_id', $lastWeekIds[1]->id)
                        ->where('m5.is_freezed', 1);
                })
                ->pluck('users.id');
            User::whereIn('id', $users)
                ->chunkById(100, function (Collection $users) {
                    DB::beginTransaction();

                    foreach ($users as $user) {
                        if ($user->hasanyrole('admin|consultant|advisor|supervisor|leader')) {
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
                    DB::commit();
                });


            return true;
        } catch (\Exception $e) {
            Log::channel('newWeek')->info("CASE2: " . $e);
        }
    }

    public function excludeUser($user, $updated_at = null)
    {
        $userGroup = UserGroup::where('user_id', $user->id)->where('user_type', 'ambassador')->whereNull('termination_reason')->first();
        if ($userGroup) {
            $userGroup->termination_reason = 'excluded';
            if (!is_null($updated_at)) {
                $userGroup->updated_at = $updated_at;
            }
            $userGroup->save();
        }
        if ($user->parent_id) {
            $msg = 'لقد تم استبعاد السفير ' . $user->name . ' من الفريق بسبب عدم التزامه بالقراءة طيلة الأسابيع الماضية';
            (new NotificationController)->sendNotification($user->parent_id, $msg, EXCLUDED_USER);
        }

        User::where('id', $user->id)->update(['parent_id' => null, 'is_excluded' => 1]);
        //Update User Parent
        UserParent::where("user_id", $user->id)->update(["is_active" => 0]);

        return true;
    }

    public function excludeNewMembers()
    {
        try {
            Log::channel('newWeek')->info("exclude new members start");

            // Previous Week [0], Before the Previous [1] , Before Before the Previous [3] and Before Before Before (😂) the Previous [4]
            $lastWeekIds = $this->get_last_weeks_ids(4);
            //current Week
            $currentWeek = Week::where('is_vacation', 0)->latest('id')->first();

            $users = User::with('roles')
                ->leftJoin('marks as m1', function ($join) use ($lastWeekIds) {
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
                ->whereRaw('COALESCE(m2.reading_mark, 0) = 0')
                ->whereRaw('COALESCE(m3.reading_mark, 0) = 0')
                ->whereRaw('COALESCE(m4.reading_mark, 0) = 0')
                ->whereBetween('users.created_at', [$lastWeekIds[3]->created_at, $lastWeekIds[2]->created_at])
                ->whereNotNull('users.email_verified_at')
                ->where('users.is_excluded', 0)
                ->pluck('users.id');
            User::whereIn('id', $users)
                ->chunkById(100, function (Collection $users) use ($lastWeekIds) {
                    DB::beginTransaction();

                    foreach ($users as $user) {
                        if ($user->hasanyrole('admin|consultant|advisor|supervisor|leader')) {
                            (new NotificationController)->sendNotification(
                                $user->parent_id,
                                'لقد حصل '  . $user->name . ' على صفرين متتالين, يرجى تنبيهه',
                                EXCLUDED_USER,
                                $this->getProfilePath($user->id)
                            );
                        } else {
                            $this->excludeUser($user, $lastWeekIds[0]->created_at);
                        }
                    }
                    DB::commit();
                });


            return true;
        } catch (\Exception $e) {
            Log::channel('newWeek')->info("exclude new members: " . $e);
        }
    }
}
