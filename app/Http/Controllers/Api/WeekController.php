<?php

namespace App\Http\Controllers\api;

use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Http\Controllers\Controller;
use App\Models\Mark;
use App\Models\User;
use App\Models\UserException;
use App\Models\Week;
use App\Traits\ResponseJson;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

use function PHPUnit\Framework\isNull;

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
        define('FREEZING_TYPE', 'freeze');
        define('EXCEPTIONAL_FREEZING_TYPE', 'exceptional freeze');
        define('EXAMS_TYPE', 'exams');
        define('MINIMUM_EXAM_MARK', 36);
        define('SUPPORT_MARK', 10);
        define('MARK_OUT_OF_90', 90);
        define('MARK_OUT_OF_100', 100);

        // dd(YEAR_WEEKS);
    }

    /**
     * Create new Week and new marks
     *
     * @return jsonResponseWithoutMessage
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

            DB::commit();
            return $this->jsonResponseWithoutMessage('Marks added Successfully', 'data', 200);
        } catch (\Exception $e) {
            // echo $e->getMessage();
            DB::rollBack();
            return $this->jsonResponseWithoutMessage('Something went wrong, Could not add marks', 'data', 200);
        }
    }

    /**
     * This function update week data based on certain permission
     * 
     * Name: update
     * @param Request $request (array of data to be updated)
     * @return jsonResponseWithoutMessage (if the validation of data failed or the updating failed/
     *                                     if the updating of data succeed)
     * @return NotFound Exception if the week to be updated is not found                 
     * @return NotAuthorized Exception if the user does not have permission            
     */
    public function update(Request $request)
    {
        //validate requested data
        $validator = Validator::make($request->all(), [
            'title'        => 'required_without:is_vacation',
            'is_vacation'     => 'required_without:title|numeric',
            'week_id' => 'required'
        ]);


        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if (Auth::user()->can('edit week')) {
            $week = Week::where('id', $request->week_id)->first();
            if ($week) {
                if ($request->has('title')) {
                    $week->title = $request->title;
                }
                if ($request->has('is_vacation')) {
                    $week->is_vacation = $request->is_vacation;
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
     * This function will search among the weeks titles of the year and give back the week title of the new week
     * 
     * Name: search_for_week_title
     * @param date $date (date of biginning week), 
     * @param array $year_weeks(array of year weeks dates and titles)
     * @return string title of the passed week date
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
     * This function will insert a new week into weeks table 
     * 
     * Name: insert_week
     * Arguments: None
     * @return int new_week_id in case of SUCCESS, 
     * @return jsonResponseWithoutMessage in case of FAIL
     */
    public function insert_week()
    {
        $week = new Week();
        //to be changed based on the full global array of weeks names

        // dd(Carbon::now()->startOfWeek(Carbon::SUNDAY)->format('Y-m-d'));

        $week->title = $this->search_for_week_title(Carbon::now()->startOfWeek(Carbon::SUNDAY)->format('Y-m-d'), YEAR_WEEKS);
        $week->is_vacation = 0;
        // $week->date = Carbon::now()->startOfWeek(Carbon::SUNDAY)->format('Y-m-d');

        if ($week->save()) { //insert new week
            return $week->id;
        } else {
            return $this->jsonResponseWithoutMessage('Something went wrong, could not add week', 'data', 500);
        }
    }

    /**
     * This function will get the last three weeks without vacations from the system
     * 
     * Name: get_last_weeks_ids
     * Arguments: None
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
     * This function will get the marks of a single user in the last three weeks
     * then check if he/she is execluded and update data 
     * then if he/she is not excluded insert mark through insert_mark_for_single_user() function
     * 
     * Name: update_excluded_user_then_add_mark
     * @param array $user (model array containing user data), 
     * @param  array $last_week_ids (array of last week ids integer), 
     * @param int $new_week_id (the current week id integer)
     * @return True if the updating excluded member or inserting new mark record is done correctly, 
     * @return Null if anything wrong happens 
     * 
     */
    public function update_excluded_user_then_add_mark($user, $last_week_ids, $new_week_id)
    {
        if (count($last_week_ids) > 0) {
            //check if the userhas exams exception then update the previous mark
            $this->check_exams_exception_for_user($new_week_id, $user->id);
        }

        if (count($last_week_ids) < 2) { //for new system
            return $this->insert_mark_for_single_user($new_week_id, $user->id);
        }

        $marks = Mark::select('out_of_100')->where('user_id', $user->id)
            ->whereIn('week_id', $last_week_ids)
            ->orderBy('week_id', 'desc')
            ->get();

        // $arrayMarks = array_map(function ($mark) {
        //     return (array)$mark;
        // }, $marks->toArray());

        // dd($marks[0]->out_of_100);

        if ($marks) {
            //if the user does not satisfy the below cases so he/she is not excluded then insert a record for him/her
            if (($marks[0]->out_of_100 != 0) ||
                ($marks[0]->out_of_100 === 0 && $marks[1]->out_of_100 > 0) ||
                ($marks[0]->out_of_100 === 0 && $marks[1]->out_of_100 === -1 && count($last_week_ids) <= 2) ||
                ($marks[0]->out_of_100 === 0 && $marks[1]->out_of_100 === -1 && count($last_week_ids) > 2 && $marks[2]->out_of_100 > 0)
            ) {
                //insert new mark record
                return $this->insert_mark_for_single_user($new_week_id, $user->id);
            }

            //check if the mark of the last week is zero
            if ($marks[0]->out_of_100 === 0) {
                //check if the mark of the week before s zero (2nd of last)
                if ($marks[1]->out_of_100 === 0) {
                    //execlude the user
                    $user->is_excluded = 1;
                    return $user->save();
                    //check if the user has been freezed in the week before (2nd of last)
                } else if (($marks[1]->out_of_100 === -1) and (count($last_week_ids) > 2)) {
                    //check if the user mark is zero in  the week befor (3rd of last)
                    if ($marks[2]->out_of_100 === 0) {
                        //execlude the user
                        $user->is_excluded = 1;
                        return $user->save();
                    }
                }
            }
        } else {
            throw new NotFound;
        }
    }

    /**
     * This function will get all the users (without excluded and hold ones) 
     * then chunck the data to begin checking on those who are excluded
     * then begin DB Transaction 
     * then update data for excluded members
     * then insert new marks records for those who are not excluded
     * then commit transaction
     * Note: update excluded members and inserting marks are done through update_excluded_user_then_add_mark() function
     * 
     * Name: add_marks_for_all_users
     * @param int $new_week_id (of the current week id), 
     * @param array $last_week_ids (array of last week ids integers)  
     * @return True if the marks and updating exculded members are done correctly, 
     * @return Exception error if anything wrong happens
     */

    public function add_marks_for_all_users($new_week_id, $last_week_ids)
    {
        //get all the users and update their records if they are excluded
        $all_users = User::where('is_excluded', 0)->where('is_hold', 0)
            // ->whereIn('id', [6, 7, 8, 9, 10, 11, 12]) //for testing - to be deleted
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
     * This function insert new mark record for a specific user
     * 
     * Name: insert_mark_for_single_user
     * @param int $new_week_id (of the current week), 
     * @param int $user_id (of the user you want to insert mark for), 
     * @param boolean $is_freezed (user is freezed or not)
     * @return int inserted_mark_id if the inserting succeed
     * @return jsonResponseWithoutMessage if the inserting went wrong  
     */
    public function insert_mark_for_single_user($new_week_id, $user_id)
    {
        $is_freezed = $this->check_freezed_user($user_id, $new_week_id);

        $mark = new Mark();
        $mark->user_id = $user_id;
        $mark->week_id = $new_week_id;
        $mark->out_of_90 = ($is_freezed ? -1 : 0);
        $mark->out_of_100 = ($is_freezed ? -1 : 0);
        $mark->total_pages = 0;
        $mark->support = 0;
        $mark->total_thesis = 0;
        $mark->total_screenshot = 0;

        if ($mark->save()) {
            return $mark->id;
        } else {
            return $this->jsonResponseWithoutMessage('Something went wrong, could not add mark', 'data', 500);
        }
    }

    /**
     * This function checks if the user is gonna be freezed this current week 
     * and update the status of the exception if the duration finished through update_exception_status() function
     * 
     * Name: check_freezed_user
     * @param int $new_week_id (of the current week), 
     * @param int $user_id (of the user you want to check exception freezing for), 
     * @return True if the user going to be freezed
     * @return False if the user finished his/her exception period or he/she has not an exception 
     */
    public function check_freezed_user($user_id, $new_week_id)
    {
        //get the duration and starting week id of the exception case if the user has one
        $user_exception = UserException::select('user_exceptions.id', 'user_exceptions.week_id', 'user_exceptions.end_at')
            ->join('exception_types', 'exception_types.id', '=', 'user_exceptions.type_id')
            ->where('user_exceptions.user_id', $user_id)
            ->where('user_exceptions.status', EXCEPTION_STATUS)
            ->where(
                function ($query) { //could be changed to the ids of the types if they are fixed in the database
                    return $query->where('exception_types.type', FREEZING_TYPE)
                        ->orWhere('exception_types.type', EXCEPTIONAL_FREEZING_TYPE);
                }
            )
            ->latest('user_exceptions.id')
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

        // //get the weeks from the beginning week of the exception till the duration of the exception
        // $weeks_of_exception = Week::where('id', '>=', $user_exception->week_id)
        //     // ->where('id', '!=', $new_week_id)
        //     ->limit($user_exception->duration)
        //     ->get();

        // // $freezed_marks = Mark::select('out_of_100')
        // //     ->where('user_id', $user_id)
        // //     ->where('out_of_100', -1)
        // //     ->where('week_id', '>=', $user_exception->week_id)
        // //     ->where('week_id', '!=', $new_week_id)
        // //     ->get();

        // if (count($weeks_of_exception) == $user_exception->duration) { //exception duration finished
        //     $this->update_exception_status($user_exception->id, 'finished');
        //     return TRUE;
        // }
        // // else if(count($weeks_of_exception) > $user_exception->duration){
        // //     return FALSE;
        // // }    
        // else if (count($weeks_of_exception) < $user_exception->duration) { //exception duration still in progress
        //     return TRUE;
        // }
        // //add duration to the starting week id to check the duration
        // $duration_from_starting_week = $user_exception->week_id + $user_exception->duration;

        // //subtract the new week id from the 
        // $remaining_duration = $duration_from_starting_week - $new_week_id;
        // $is_freezed = ($remaining_duration > 0 ? TRUE : FALSE);
    }

    /**
     * This function update the user exception status to a new one
     * 
     * Name: update_exception_status
     * @param int $user_exception_id (of the current exception), 
     * @param string $new_status (of the new status), 
     * @return True if the status updated successfully
     * @return jsonResponseWithoutMessage if the updating went wrong 
     */
    public function update_exception_status($user_exception_id, $new_status)
    {
        //get the exception record of the user
        $user_exception = UserException::where('id', $user_exception_id)
            ->latest('user_exceptions.id')
            ->first();

        //update record with the new status    
        $user_exception->status = $new_status;
        if ($user_exception->save()) {
            return FALSE;
        } else {
            return $this->jsonResponseWithoutMessage('could not update user exception', 'data', 500);
        }
    }

    /**
     * This function checks if the user is having exams exception this current week 
     * and update the mark of the user if the rules are satisfied through update_exams_mark_for_user() function
     * and update the status of the exception if the duration finished through update_exception_status() function
     * 
     * Name: check_exams_exception_for_user
     * @param int $new_week_id (of the current week), 
     * @param int $user_id (of the user you want to check exams exception for), 
     * @return True if the user going to have exams exception
     * @return False if the user finished his/her exception period, 
     *               or he/she has not an exception, 
     *               or he/she does not satisfy the rules
     */
    public function check_exams_exception_for_user($new_week_id, $user_id)
    {
        //get the user exams exception 
        $user_exception = UserException::select('user_exceptions.id', 'user_exceptions.week_id', 'user_exceptions.end_at')
            ->join('exception_types', 'exception_types.id', '=', 'user_exceptions.type_id')
            ->where('user_exceptions.user_id', $user_id)
            ->where('user_exceptions.status', EXCEPTION_STATUS)
            ->where('exception_types.type', EXAMS_TYPE)
            ->latest('user_exceptions.id')
            ->first();


        if ($user_exception === null) {
            return FALSE;
        }

        //get previous week
        $previous_week_id = Week::where('id', '!=', $new_week_id)->latest('id')->limit(1)->first()->id;

        //upadate user mark
        $this->update_exams_mark_for_user($user_id, $previous_week_id);

        $end_at = Carbon::parse($user_exception->end_at)->format('Y-m-d');
        $current_date = Carbon::now()->format('Y-m-d');

        if ($current_date >= $end_at) { //exception duration finished
            $this->update_exception_status($user_exception->id, 'finished');
            return FALSE;
        } else { //exception duration still in progress
            return TRUE;
        }

        // //get the weeks from the beginning week of the exception till the duration of the exception
        // $weeks_of_exception = Week::where('id', '>=', $user_exception->week_id)
        //     ->where('id', '!=', $new_week_id)
        //     ->limit($user_exception->duration)
        //     ->get();

        // if (count($weeks_of_exception) == $user_exception->duration) { //exception finished            
        //     return $this->update_exception_status($user_exception->id, 'finished');
        // } else if (count($weeks_of_exception) < $user_exception->duration) { //exception duration still in progress
        //     return TRUE;
        // }
    }

    /**
     * This function update the user mark for exams exception
     * 
     * Name: update_exams_mark_for_user
     * @param int $user_id (of the user you want to update mark for), 
     * @param int $previous_week_id (of the week of the user mark)
     * @return True if the mark updated successfully
     * @return Fales if the user does not satisfy the exams rules
     * @return jsonResponseWithoutMessage if the updating went wrong 
     */
    public function update_exams_mark_for_user($user_id, $previous_week_id)
    {
        //get user mark record
        $user_mark = Mark::where('user_id', $user_id)->where('week_id', $previous_week_id)->first();

        //RULES OF EXAMS EXCEPTION
        if ($user_mark->out_of_90 >= MINIMUM_EXAM_MARK and $user_mark->support == SUPPORT_MARK) {
            $user_mark->out_of_90 = MARK_OUT_OF_90;
            $user_mark->out_of_100 = MARK_OUT_OF_100;
            if ($user_mark->save()) {
                return TRUE;
            } else {
                return $this->jsonResponseWithoutMessage('could not update user mark', 'data', 500);
            }
        } else {
            return FALSE;
        }
    }
}