<?php

namespace App\Http\Controllers\api;

use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Http\Controllers\Controller;
use App\Models\Mark;
use App\Models\User;
use App\Models\UserException;
use App\Models\UserGroup;
use App\Models\Week;
use App\Models\UserStatistic;
use App\Models\MarkStatistic;
use App\Traits\ResponseJson;
use App\Events\UpdateUserStats;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WeekController extends Controller
{
    use ResponseJson;
    public function __construct()
    {
        $now = Carbon::now();
        define(
            'YEAR_WEEKS',
            array(
                array('title' => 'الأول من أغسطس', 'date' => $now->startOfWeek(Carbon::SUNDAY)->format('Y-m-d')),
                array('title' => 'الثاني من أغسطس', 'date' => $now->startOfWeek(Carbon::SUNDAY)->addWeeks()->format('Y-m-d')),
                array('title' => 'الثالث من أغسطس', 'date' => $now->startOfWeek(Carbon::SUNDAY)->addWeeks()->format('Y-m-d')),
                array('title' => 'الرابع من أغسطس', 'date' => $now->startOfWeek(Carbon::SUNDAY)->addWeeks()->format('Y-m-d')),
            )
        );

        define('EXCEPTION_STATUS', 'accepted');
        define('FREEZ_THIS_WEEK_TYPE', 'تجميد الأسبوع الحالي');
        define('FREEZ_NEXT_WEEK_TYPE', 'تجميد الأسبوع القادم');
        define('EXCEPTIONAL_FREEZING_TYPE', 'تجميد استثنائي');
        define('SUPPORT_MARK', 10);
        define('READING_MARK', 50);
        define('WRITING_MARK', 40);
        define('EXAMS_MONTHLY_TYPE', 'نظام امتحانات - شهري');
        define('EXAMS_SEASONAL_TYPE', 'نظام امتحانات - فصلي');
    }

    /**
     * Add new week with mark records for all users in the system
     * @author Asmaa     
     * @todo get last three weeks ids
     * @todo add new week to the system
     * @todo add marks for all users
     * @todo add users statistics
     * @todo add marks statistics
     * @return ResponseJson
     */
    public function create()
    {
        //get last three weeks ids
        $last_week_ids = $this->get_last_weeks_ids();
        // dd($last_week_ids);

        DB::beginTransaction();
        try {
            //add new week to the system
            $new_week_id = $this->insert_week();

            $this->add_marks_for_all_users($new_week_id, $last_week_ids);
            $this->add_users_statistics($new_week_id);
            $this->add_marks_statistics($new_week_id);

            DB::commit();
            return $this->jsonResponseWithoutMessage('Marks added Successfully', 'data', 200);
        } catch (\Exception $e) {
            // echo $e->getMessage();
            DB::rollBack();
            return $this->jsonResponseWithoutMessage('Something went wrong, Could not add marks', 'data', 200);
        }
    }

    /**
     * update week data based on certain permission
     * @author Asmaa - Sara
     * @param Request $request (array of data to be updated)
     * @return jsonResponse (if the validation of data failed or the updating failed/if the updating of data succeed)
     * @return NotFound Exception if the week to be updated is not found                 
     * @return NotAuthorized Exception if the user does not have permission            
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
                            (new NotificationController)->sendNotification($exception->user_id, $msg);
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
     * search for week title based on the date of the week
     * @author Asmaa     
     * @param Date $date (date of biginning week), 
     * @param Array $year_weeks(array of year weeks dates and titles)
     * @return String title of the passed week date
     * @return Null if not found
     */
    public function search_for_week_title($date, $year_weeks)
    {
        foreach ($year_weeks as $val) {
            if ($val['date'] === $date) {
                return $val['title'];
            }
        }
        return null;
    }

    /**
     * insert new week into weeks table
     * @author Asmaa         
     * @return Int new_week_id in case of SUCCESS, 
     * @return jsonResponse in case of FAIL
     */
    public function insert_week()
    {
        $week = new Week();

        //to be changed based on the full global array of weeks names
        $week->title = $this->search_for_week_title(Carbon::now()->startOfWeek(Carbon::SUNDAY)->format('Y-m-d'), YEAR_WEEKS);
        $week->is_vacation = 0;

        if ($week->save()) { //insert new week
            return $week->id;
        } else {
            return $this->jsonResponseWithoutMessage('Something went wrong, could not add week', 'data', 500);
        }
    }

    /**
     * get the last three weeks without vacations from the system
     * @author Asmaa
     * @param int $limit = 3
     * @return array of week_ids of last three weeks,
     * @return Null if no weeks were found
     */
    public function get_last_weeks_ids($limit = 3)
    {
        //get last weeks without vacations from the data
        $last_weeks = Week::where('is_vacation', 0)->latest('id')->limit($limit)->get('id');

        // dd($last_weeks);
        $last_week_ids = array();

        if ($last_weeks) {
            //fill week ids into array
            foreach ($last_weeks as $week) {
                array_push($last_week_ids, $week->id);
            }
        }

        return $last_week_ids;
    }

    /**
     * update excluded user then add mark
     * @author Asmaa     
     * @todo 1- check exam exception for user and update status if finished
     * @todo 2- if there are less than 2 weeks in the system then insert mark for single user without checking for excluded users
     * @todo 3- if there are more than 2 weeks in the system 
     * @todo 3.1- get last week marks for the user
     * @todo 3.2- check if the user excluded (two consecutive zeros, zero - freezed - zero)
     * @todo 3.3- if the user is excluded then update the status of the user to excluded
     * @todo 3.4- if the user is not excluded then insert mark for single user
     * @param Object $user 
     * @param Array $last_week_ids
     * @param Int $new_week_id
     * @uses insert_mark_for_single_user()
     * @uses check_exams_exception_for_user()
     * @uses UpdateUserStats Event
     * @return True if the updating excluded member or inserting new mark record is done correctly, 
     * @return Null if anything wrong happens 
     * 
     */
    public function update_excluded_user_then_add_mark($user, $last_week_ids, $new_week_id)
    {
        if (count($last_week_ids) > 0) {
            //check if the user has exams exception then update the status if finished
            $this->check_exams_exception_for_user($new_week_id, $user->id);
        }

        if (count($last_week_ids) < 2) { //for new system
            return $this->insert_mark_for_single_user($new_week_id, $user->id);
        }

        $marks = Mark::where('user_id', $user->id)
            ->whereIn('week_id', $last_week_ids)
            ->orderBy('week_id', 'desc')
            ->get();

        if ($marks) {
            $mark_last_week = $marks[0]->reading_mark + $marks[0]->writing_mark + $marks[0]->support;
            $mark_second_last_week = $marks[1]->reading_mark + $marks[1]->writing_mark + $marks[1]->support;
            $mark_third_last_week = $marks[2]->reading_mark + $marks[2]->writing_mark + $marks[2]->support;

            //if the user does not satisfy the below cases so he/she is not excluded then insert a record for him/her
            if (($mark_last_week !== 0) ||
                ($mark_last_week === 0 && $mark_second_last_week > 0) ||
                ($mark_last_week === 0 && $marks[1]->is_freezed && count($last_week_ids) <= 2) ||
                ($mark_last_week === 0 && $marks[1]->is_freezed && count($last_week_ids) > 2 && $mark_third_last_week > 0)
            ) {
                //insert new mark record
                return $this->insert_mark_for_single_user($new_week_id, $user->id);
            }

            $old_user = $user->getOriginal();
            //check if the mark of the last week is zero
            if ($mark_last_week === 0) {
                //check if the mark of the week before is zero (2nd of last)
                if ($mark_second_last_week === 0) {
                    //execlude the user
                    $user->is_excluded = 1;
                    $user = $user->save();
                    event(new UpdateUserStats($user, $old_user));
                    return $user;

                    //check if the user has been freezed in the week before (2nd of last)
                } else if (($marks[1]->is_freezed) and (count($last_week_ids) > 2)) {
                    //check if the user mark is zero in  the week befor (3rd of last)
                    if ($mark_third_last_week === 0) {
                        //execlude the user
                        $user->is_excluded = 1;
                        $user = $user->save();
                        event(new UpdateUserStats($user, $old_user));
                        return $user;
                    }
                }
            }
        } else {
            throw new NotFound;
        }
    }

    /**
     * add mark record for each user, except the excluded and hold users
     * @author Asmaa
     * @todo get all the users except the excluded and hold users
     * @todo chunk the data to begin checking on those who are excluded
     * @todo update the excluded users
     * @todo insert new marks records for those who are not excluded
     * @param Int $new_week_id (of the current week id), 
     * @param array $last_week_ids (array of last week ids integers)  
     * @uses update_excluded_user_then_add_mark() 
     * @return True if the marks and updating exculded members are done correctly, 
     * @return Exception error if anything wrong happens
     */

    public function add_marks_for_all_users($new_week_id, $last_week_ids)
    {
        //get all the users and update their records if they are excluded
        $all_users = User::where('is_excluded', 0)->where('is_hold', 0)
            ->whereIn('id', [6, 7, 8, 9, 10, 11, 12]) //for testing - to be deleted
            ->orderBy('id')
            ->chunkByID(100, function ($users) use ($last_week_ids, $new_week_id) {
                try {
                    //to avoid executing an updated Statement for every single user in our DB
                    DB::beginTransaction();

                    foreach ($users as $user) {
                        //update execluded member then insert mark
                        $this->update_excluded_user_then_add_mark($user, $last_week_ids, $new_week_id);
                    }

                    DB::commit();
                } catch (\Exception $exception) {
                    Log::error($exception);
                    DB::rollBack();
                }
            }, 'users.id', 'id');

        return $all_users;
    }

    /**
     * insert new mark record for a specific user
     * @author Asmaa
     * @param Int $week_id
     * @param Int $user_id      
     * @return Int inserted_mark_id if the inserting succeed
     * @return jsonResponse if the inserting went wrong  
     */
    public function insert_mark_for_single_user($week_id, $user_id)
    {
        $is_freezed = $this->check_freezed_user($user_id, $week_id);

        $mark = new Mark();
        $mark->user_id = $user_id;
        $mark->week_id = $week_id;
        $mark->reading_mark = 0;
        $mark->writing_mark = 0;
        $mark->total_pages = 0;
        $mark->support = 0;
        $mark->total_thesis = 0;
        $mark->total_screenshot = 0;
        $mark->is_freezed = $is_freezed;

        if ($mark->save()) {
            return $mark->id;
        } else {
            return $this->jsonResponseWithoutMessage('Something went wrong, could not add mark', 'data', 500);
        }
    }

    /**
     * check if the user is gonna be freezed or not
     * @author Asmaa     
     * @todo get the exception record of the user of types freeze
     * @todo check if the exception duration finished, update the status of the exception if it finished  
     * @param Int $user_id 
     * @return True if the user going to be freezed
     * @return False if the user finished his/her exception period or he/she has no exception 
     */
    public function check_freezed_user($user_id)
    {
        //get the duration and starting week id of the exception case if the user has one
        $user_exception = UserException::where('user_id', $user_id)
            ->where('status', EXCEPTION_STATUS)
            ->with('type', function ($query) {
                $query->where('type', FREEZ_THIS_WEEK_TYPE)
                    ->orWhere('type', FREEZ_NEXT_WEEK_TYPE)
                    ->orWhere('type', EXCEPTIONAL_FREEZING_TYPE);
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
     * update the status of the exception
     * @author Asmaa     
     * @todo get the exception record of the user
     * @todo update record with the new status
     * @param Int $user_exception_id 
     * @param string $new_status
     * @return True if the status updated successfully
     * @return jsonResponse if the updating went wrong 
     */
    public function update_exception_status($user_exception_id, $new_status)
    {
        //get the exception record of the user
        $user_exception = UserException::where('id', $user_exception_id)
            ->latest('id')
            ->first();

        //update record with the new status    
        $user_exception->status = $new_status;
        if ($user_exception->save()) {
            return TRUE;
        } else {
            return $this->jsonResponseWithoutMessage('could not update user exception', 'data', 500);
        }
    }

    /**  
     * check if the user is having exams exception this current week   
     * @todo get the exception record of the user of type exams
     * @todo check if the exception duration finished, update the status of the exception if it finished     
     * @author Asmaa
     * @param Int $new_week_id
     * @param Int $user_id
     * @uses update_exception_status()
     * @return True if the user going to have exams exception
     * @return False if the user finished his/her exception period, 
     *               or he/she has not an exception, 
     *               or he/she does not satisfy the rules
     */
    public function check_exams_exception_for_user($new_week_id, $user_id)
    {
        //get the user exams exception 
        $user_exception = UserException::where('user_id', Auth::id())
            ->where('status', EXCEPTION_STATUS)
            ->with('type', function ($query) {
                $query->where('type', EXAMS_MONTHLY_TYPE)
                    ->orWhere('type', EXAMS_SEASONAL_TYPE);
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
     * insert new row to user_stats in database when the new week is starting.
     * @author Sara     
     * @param Int $new_week_id (integer id of the current week id), 
     * @return Int $user_statistic (integer id of the new row in user_statistic table)
     * @return JsonResponse error if anything wrong happens
     */
    public function add_users_statistics($new_week_id)
    {
        $user_stats = new UserStatistic();
        $user_stats->week_id = $new_week_id;
        $user_stats->total_new_users = 0;
        $user_stats->total_hold_users = 0;
        $user_stats->total_excluded_users = 0;
        if ($user_stats->save()) {
            return $user_stats->id;
        } else {
            return $this->jsonResponseWithoutMessage('Something went wrong, could not add users statistics', 'data', 500);
        }
    }

    /**
     * insert new row to mark_stats in database when the new week is starting.
     * @author Sara     
     * @param Int $new_week_id (integer id of the current week id),
     * @return Int $mark_statistic_id (integer id of the new row in mark_statistic table)
     * @return JsonResponse error if anything wrong happens     
     */
    public function add_marks_statistics($new_week_id)
    {
        $mark_stats = new MarkStatistic();
        $mark_stats->week_id = $new_week_id;
        $mark_stats->total_marks_users = 0;
        $mark_stats->general_average_reading = 0;
        $mark_stats->total_users_have_100 = 0;
        $mark_stats->total_pages = 0;
        $mark_stats->total_thesises = 0;
        if ($mark_stats->save()) {
            return $mark_stats->id;
        } else {
            return $this->jsonResponseWithoutMessage('Something went wrong, could not add mark', 'data', 500);
        }
    }
}