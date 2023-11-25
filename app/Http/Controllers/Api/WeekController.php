<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Http\Controllers\Controller;
use App\Models\Mark;
use App\Models\User;
use App\Models\UserException;
use App\Models\Week;
use App\Traits\ResponseJson;
use App\Models\Post;
use App\Models\PostType;
use App\Models\UserGroup;
use App\Traits\PathTrait;
use App\Traits\ThesisTraits;
use App\Traits\WeekTrait;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WeekController extends Controller
{
    use ResponseJson, ThesisTraits, PathTrait, WeekTrait;

    protected $excludedUsers = [];
    protected $newWeekId = null;
    protected $lastWeekIds = [];

    /**
     * Add new week with mark records for all users in the system
     * @author Asmaa     
     * get last three weeks ids
     * add new week to the system
     * add marks for all users
     * add users statistics
     * add marks statistics
     * @return ResponseJson
     */
    public function create()
    {
        $this->excludedUsers = [];
        //get last three weeks ids
        $this->lastWeekIds = $this->get_last_weeks_ids();
        // dd($this->lastWeekIds);

        //close books and support comments
        $this->closeBooksAndSupportComments();


        DB::beginTransaction();
        try {
            Log::channel('newWeek')->info("begin");

            //add new week to the system
            $this->newWeekId = $this->insert_week();

            $new_week = Week::find($this->newWeekId);

            $dateToAdd = new Carbon($new_week->main_timer);
            $new_week->modify_timer = $dateToAdd->addHours(23);
            $new_week->save();

            if (!$new_week->is_vacation) {
                $this->check_all_users();
            }

            DB::commit();
            $this->openBooksComments();

            if ($new_week->is_vacation) {
                $this->notifyUsersIsVacation();
            } else {
                $this->notifyUsersNewWeek();
                $this->notifyExcludedUsers();
            }
            Log::channel('newWeek')->info("Week Added Successfully");
        } catch (\Exception $e) {
            $this->openBooksComments();
            Log::channel('newWeek')->info($e);
            Log::error($e);

            // echo $e->getMessage();
            DB::rollBack();
            return $this->jsonResponseWithoutMessage($e->getMessage() . ' at line ' . $e->getLine(), 'data', 500);
        }
    }

    /**
     * update week data based on certain permission
     * @author Asmaa - Sara
     * @param Request $request (array of data to be updated)
     * @return jsonResponse (if the validation of data failed or the updating failed/if the updating of data succeed)
     * @throws NotFound Exception if the week to be updated is not found                 
     * @throws NotAuthorized Exception if the user does not have permission            
     */
    public function update(Request $request)
    {
        //validate requested data
        $validator = Validator::make($request->all(), [
            'title'       => 'required_without:is_vacation',
            'is_vacation' => 'required_without:title|in:1',
            'week_id'     => 'required|numeric'
        ]);


        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if (Auth::user()->can('edit week')) {
            $week = Week::find($request->week_id);
            if ($week) {
                if ($request->has('title')) {
                    $week->title = $request->title;
                }
                if ($request->has('is_vacation')) {
                    if ($week->is_vacation == 0) {
                        $week->is_vacation = $request->is_vacation;
                        $exceptions = UserException::where('status', 'accepted')->whereDate('end_at', '>', Carbon::now())->get();
                        foreach ($exceptions as $exception) {
                            $lengthIndays = Carbon::parse($exception->end_at)->diffInDays();
                            $exception->end_at = (Carbon::parse($exception->end_at)->addDays($lengthIndays))->format('Y-m-d');
                            $exception->update();

                            $msg = "تم تمديد الحالة الاستثنائية لك حتى " . $exception->end_at . " بسبب الإجازة";
                            (new NotificationController)->sendNotification($exception->user_id, $msg, USER_EXCEPTIONS, $this->getExceptionPath($exception->id));
                        }
                    } else { //this week is already vacation
                        return $this->jsonResponseWithoutMessage('This week is already vacation', 'data', 200);
                    }
                }

                if ($week->save()) {
                    return $this->jsonResponseWithoutMessage('Week updated successfully', 'data', 200);
                } else {
                    return $this->jsonResponseWithoutMessage('Cannot update week', 'data', 500);
                }
            } else {
                throw new NotFound;
            }
        } else {
            throw new NotAuthorized;
        }
    }

    /**
     * search for week is vacation based on the date of the week
     * @author Sara     
     * @param Date $date (date of biginning week), 
     * @param Array $year_weeks(array of year weeks dates and titles)
     * @return int is_vacation of the passed week date
     * @return Null if not found
     */
    private function search_for_is_vacation($date, $year_weeks)
    {
        foreach ($year_weeks as $val) {
            if ($val['date'] === $date) {
                return $val['is_vacation'];
            }
        }
        return null;
    }



    /**
     * insert new week into weeks table
     * @author Asmaa         
     * @throws Exception error if anything wrong happens
     */
    private function insert_week()
    {
        $previousWeek = Week::latest('id')->first();
        $previousDate = $previousWeek ? Carbon::parse($previousWeek->created_at) : null;

        $week = new Week();

        $currentDate = Carbon::now();

        $currentDate->hour = 0;
        $currentDate->minute = 0;
        $currentDate->second = 0;

        $date = $currentDate;

        //check if $date is saturday or not
        if ($currentDate->dayOfWeek != Carbon::SATURDAY) {
            //check if the previous saturday is equal to the previous week date
            $date = $currentDate->previous(Carbon::SATURDAY);
            if ($previousDate && $previousDate->format('Y-m-d') == $date->format('Y-m-d')) {

                $date = $currentDate->next(Carbon::SATURDAY);
            }
        }

        //seach sundays
        $dateToSearch = $date->addDay();
        $week->title = $this->search_for_week_title($dateToSearch->format('Y-m-d'), config('constants.YEAR_WEEKS'));
        //seach is_vacation
        $week->is_vacation = $this->search_for_is_vacation($dateToSearch->format('Y-m-d'), config('constants.YEAR_WEEKS'));

        //add end of saturdays
        $dateToAdd = $date->subDay()->addHours(23)->addMinutes(59)->addSeconds(59);
        $week->created_at = $dateToAdd;
        $week->updated_at = $dateToAdd;

        //add 7 days to the date to get the end of the week
        $week->main_timer = $dateToAdd->addDays(7);

        if ($week->save()) { //insert new week
            return $week->id;
        }
        // return $this->jsonResponseWithoutMessage('Something went wrong, could not add week', 'data', 500);
        throw new \Exception('Something went wrong, could not add week');
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
        //get last weeks without vacations from the data
        return Week::where('is_vacation', 0)->latest('id')->limit($limit)->pluck('id');
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
            // ->where('id', '>=', 18)
            // ->whereIn('id', [19, 20, 21, 22, 23]) //for testing - to be deleted
            ->orderBy('id')
            ->chunkByID(100, function ($users) {
                try {
                    //to avoid executing an updated Statement for every single user in our DB
                    DB::beginTransaction();

                    foreach ($users as $user) {
                        //update execluded member then insert mark
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
        if (count($this->lastWeekIds) > 0) {
            //check if the user has exams exception then update the status if finished
            $this->check_exams_exception_for_user($user->id);
        }

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

            //execlude the user
            $user->is_excluded = 1;
            // $user->parent_id = null; //moved to the notifyExcludedUser function
            array_push($this->excludedUsers, $user->id);
            $user->save();

            // event(new UpdateUserStats($user, $old_user));
            return $user;
        }
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
     * check if the user is freezed in a week or not then update the status of the user if he/she is freezed
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
                $query->where('type', config('constants.FREEZ_THIS_WEEK_TYPE'))
                    ->orWhere('type', config('constants.FREEZ_NEXT_WEEK_TYPE'))
                    ->orWhere('type', config('constants.EXCEPTIONAL_FREEZING_TYPE'));
            })
            ->whereDate('start_at', '>=', $week_startAt)
            ->whereDate('end_at', '<=', $week_endAt)
            ->latest('id')
            ->first();

        //update the status of the exception if it finished
        if ($user_exception && $user_exception->status == config('constants.ACCEPTED_STATUS')) {
            $end_at = Carbon::parse($user_exception->end_at)->format('Y-m-d');
            $current_date = Carbon::now()->format('Y-m-d');

            if ($current_date >= $end_at) { //exception duration finished
                $this->update_exception_status($user_exception->id, 'finished');
            }
        }

        return $user_exception === null ? FALSE : TRUE;
    }

    /**
     * update the status of the exception
     * @author Asmaa     
     * get the exception record of the user
     * update record with the new status
     * @param Int $user_exception_id 
     * @param string $new_status
     * @return True if the status updated successfully
     * @throws Exception error if anything wrong happens
     */
    private function update_exception_status($user_exception_id, $new_status)
    {
        //get the exception record of the user
        $user_exception = UserException::where('id', $user_exception_id)
            ->latest('id')
            ->first();

        //update record with the new status    
        $user_exception->status = $new_status;
        if ($user_exception->save()) {
            if ($new_status == 'finished') {
                $message = 'لقد انتهت الفترة الخاصة ب ';
                $exceptionTitle = $user_exception->type->type;
                if (Str::contains($user_exception->type->type, 'تجميد')) {
                    $exceptionTitle = 'نظام ' . $user_exception->type->type;
                }
                //notify the user that his/her exception finished
                //$user->notify(new MailExceptionFinished($exceptionTitle));
                (new NotificationController)->sendNotification($user_exception->user_id, $message . $exceptionTitle, USER_EXCEPTIONS, $this->getExceptionPath($user_exception->id));
            }
            return TRUE;
        }
        // return $this->jsonResponseWithoutMessage('could not update user exception', 'data', 500);
        throw new \Exception('could not update user exception');
    }

    /**  
     * check if the user is having exams exception this current week   
     * get the exception record of the user of type exams
     * check if the exception duration finished, update the status of the exception if it finished     
     * @author Asmaa
     * @param Int $user_id
     * @uses update_exception_status()
     * @return True if the user going to have exams exception
     * @return False if the user finished his/her exception period, 
     *               or he/she has not an exception, 
     *               or he/she does not satisfy the rules
     */
    private function check_exams_exception_for_user($user_id)
    {
        //get the user exams exception 
        $user_exception = UserException::where('user_id', $user_id)
            ->where('status', config('constants.ACCEPTED_STATUS'))
            ->with('type', function ($query) {
                $query->where('type', config('constants.EXAMS_MONTHLY_TYPE'))
                    ->orWhere('type', config('constants.EXAMS_SEASONAL_TYPE'));
            })
            ->latest('id')
            ->first();

        if ($user_exception === null) {
            return FALSE;
        }

        $end_at = Carbon::parse($user_exception->end_at)->format('Y-m-d');
        $current_date = Carbon::now()->format('Y-m-d');

        if ($current_date >= $end_at) { //exception duration finished
            $this->update_exception_status($user_exception->id, 'finished');
            return FALSE;
        } else { //exception duration still in progress
            return TRUE;
        }
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
            $mark_second_last_week && $second_last_week_freezed &&
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
     * Close the comments on books and support posts
     * @return JsonResponse
     */
    private function closeBooksAndSupportComments()
    {
        $posts = Post::whereIn('type_id', PostType::whereIn('type', ['book', 'support'])->pluck('id')->toArray())
            ->chunk(100, function ($posts) {
                try {
                    DB::beginTransaction();

                    foreach ($posts as $post) {
                        $post->update(['allow_comments' => 0]);
                    }

                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollback();
                    throw $e;
                }
            });

        return $this->jsonResponseWithoutMessage('تم إغلاق التعليقات على الكتب والدعم', 'data', 200);
    }

    /**
     * Open the comments on books
     */
    private function openBooksComments()
    {
        $posts = Post::where('type_id', PostType::where('type', 'book')->first()->id)
            ->chunk(100, function ($posts) {
                try {
                    DB::beginTransaction();

                    foreach ($posts as $post) {
                        $post->update(['allow_comments' => 1]);
                    }

                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollback();
                    throw $e;
                }
            });

        // كتاب متعة الحديث محذوف من المنهج
        Post::where('book_id', 599)
            ->update(['allow_comments' => 0]);

        return $this->jsonResponseWithoutMessage('تم فتح التعليقات على الكتب', 'data', 200);
    }


    /**
     * Notify all the users that a new week has started
     * @return JsonResponse
     */
    private function notifyUsersNewWeek()
    {
        $notification = new NotificationController();
        User::where('is_excluded', 0)->where('is_hold', 0)
            ->chunk(100, function ($users) use ($notification) {
                try {
                    $msg = 'لقد بدأ أسبوع أصبوحي جديد, جدد النية 💪';
                    foreach ($users as $user) {
                        $notification->sendNotification($user->id, $msg, NEW_WEEK);
                    }
                } catch (\Exception $e) {
                    throw $e;
                }
            });
    }


    /**
     * Notify all the users that a this week is a vacation
     * @return JsonResponse
     */
    private function notifyUsersIsVacation()
    {
        $notification = new NotificationController();
        User::where('is_excluded', 0)->where('is_hold', 0)
            ->chunk(100, function ($users) use ($notification) {
                try {
                    $msg = 'إجازة عيد الأضحى المبارك السنوية 🐑';
                    foreach ($users as $user) {
                        $notification->sendNotification($user->id, $msg, NEW_WEEK);
                    }
                } catch (\Exception $e) {
                    throw $e;
                }
            });
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
            $groups = UserGroup::whereIn('user_id', $this->excludedUsers)->where('user_type', 'ambassador')->whereNull('termination_reason')->get();

            try {
                DB::beginTransaction();
                foreach ($groups as $group) {
                    $group->termination_reason = 'excluded';
                    $group->save();
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

            foreach ($users as $user) {
                $msg = 'لقد تم استبعاد السفير ' . $user->name . ' من الفريق بسبب عدم التزامه بالقراءة طيلة الأسابيع الماضية';
                $notification->sendNotification($user->parent_id, $msg, EXCLUDED_USER);
                $user->parent_id = null;
                $user->save();
                // $user->notify(
                //     (new \App\Notifications\MailExcludeAmbassador())
                //         ->delay(now()->addMinutes(5))
                // );

            }
        } catch (\Exception $e) {
            throw $e;
        }
    }



    /**
     * set_modify_timer
     * @author Sara              
     * @throws Exception error if anything wrong happens
     */
    public function set_modify_timer()
    {
        try {
            $previous_week = Week::orderBy('created_at', 'desc')->skip(1)->take(2)->first();
            if ($previous_week && !$previous_week->is_vacation) {
                $dateToAdd = new Carbon($previous_week->modify_timer);
                $previous_week->modify_timer = $dateToAdd->addDays(4)->addHours(2);
                $previous_week->save();
                Log::channel('newWeek')->info("modify_timer updated Successfully");
            }
        } catch (\Exception $e) {
            Log::channel('newWeek')->info($e);
        }
    }
}
