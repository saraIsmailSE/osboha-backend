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

        define('EXCEPTION_STATUS', 'pending');
        define('FREEZING_TYPE', 'freeze');
        define('EXCEPTIONAL_FREEZING_TYPE', 'exceptional freeze');

        // dd(YEAR_WEEKS);
    }

    public function create()
    {
        //get last three weeks ids
        $last_week_ids = $this->get_last_weeks_ids();
        // dd($last_week_ids);
        //add new week to the system
        $new_week_id = $this->insert_week();

        if ($this->add_marks_for_all_users($new_week_id, $last_week_ids)) {
            return $this->jsonResponseWithoutMessage('Marks added Successfully', 'data', 200);
        } else {
            return $this->jsonResponseWithoutMessage('Something went wrong, Could not add marks', 'data', 200);
        }
    }

    /**
     * This function update week data based on certain permission
     * 
     * Name: update
     * Arguments: $request (array of data to be updated)
     * Return: Json error message if the validation of data failed or the updating failed,
     *         Json success message if the updating of data succeed
     *         NotFound Exception if the week to be updated is not found                 
     *         NotAuthorized Exception if the user does not have permission            
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
     * Arguments: $date (date of biginning week), 
     *            $year_weeks(array of year weeks dates and titles)
     * Return: title of the passed week date
     *         Null if not found
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
     * Return: new week id in case of SUCCESS, 
     *         Error JsonResponse in case of FAIL
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
     * Return: Array with week_ids of last three weeks,
     *         Null if no weeks were found
     */
    public function get_last_weeks_ids()
    {
        //get last three weeks without vacations from the data
        $last_weeks = Week::where('is_vacation', 0)->latest('id')->limit(3)->get('id');

        // dd($last_weeks);
        $last_week_ids = array();

        if ($last_weeks) {
            //fill week ids into array
            foreach ($last_weeks as $week) {
                array_push($last_week_ids, $week->id);
            }
            return $last_week_ids;
        }

        return null;
    }

    /**
     * This function will get the marks of a single user in the last three weeks
     * then check if he/she is execluded and update data 
     * then if he/she is not excluded insert mark through insert_mark_for_single_user() function
     * 
     * Name: update_excluded_user_then_add_mark
     * Arguments: $user (model array containing user data), 
     *            $last_week_ids (array of last week ids integer), 
     *            $new_week_id (the current week id integer)
     * Return: True if the updating excluded member or inserting new mark record is done correctly, 
     *         Null if anything wrong happens 
     * 
     */
    public function update_excluded_user_then_add_mark($user, $last_week_ids, $new_week_id)
    {
        $is_freezed = $this->check_freezed_user($user->id, $new_week_id);

        $marks = Mark::select('out_of_100')->where('user_id', $user->id)
            ->whereIn('week_id', $last_week_ids)
            ->orderBy('week_id', 'desc')
            ->get();

        // $arrayMarks = array_map(function ($mark) {
        //     return (array)$mark;
        // }, $marks->toArray());

        // dd($marks[0]->out_of_100);

        if ($marks) {
            //check if the mark of the last week is zero
            if ($marks[0]->out_of_100 === 0) {
                //check if the mark of the week before s zero (2nd of last)
                if ($marks[1]->out_of_100 === 0) {
                    //execlude the user
                    $user->is_excluded = 1;
                    return $user->save();
                    //check if the user has been freezed in the week before (2nd of last)
                } else if ($marks[1]->out_of_100 === -1) {
                    //check if the user mark is zero in  the week befor (3rd of last)
                    if ($marks[2]->out_of_100 === 0) {
                        //execlude the user
                        $user->is_excluded = 1;
                        return $user->save();
                    }
                }
            }
            //if the user does not satisfy the above cases so he/she is not excluded then insert a record for him/her
            if (($marks[0]->out_of_100 != 0) ||
                ($marks[0]->out_of_100 === 0 && $marks[1]->out_of_100 > 0) ||
                ($marks[0]->out_of_100 === 0 && $marks[1]->out_of_100 === -1 && $marks[2]->out_of_100 > 0)
            ) {
                //insert new mark record
                return $this->insert_mark_for_single_user($new_week_id, $user->id, $is_freezed);
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
     * Arguments: $new_week_id (integer id of the current week id), 
     *            $last_week_ids (array of last week ids integers)  
     * Return: True if the marks and updating exculded members are done correctly, 
     *         Exception error if anything wrong happens
     */

    public function add_marks_for_all_users($new_week_id, $last_week_ids)
    {

        // $second_last_week_id = Week::where('date', Carbon::now()->subWeek(2)->startOfWeek(Carbon::SUNDAY)->format('Y-m-d'))->first()->id;
        // $third_last_week_id = Week::where('date', Carbon::now()->subWeek(3)->startOfWeek(Carbon::SUNDAY)->format('Y-m-d'))->first()->id;

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
     * This function insert new mark record for a specific user
     * 
     * Name: insert_mark_for_single_user
     * Arguments: $new_week_id (integer id of the current week), 
     *            $user_id (integer id of the user you want to insert mark for), 
     *            $is_freezed (Boolean value if the user freezed or not)
     * Return: inserted mark id if the inserting succeed
     *         json error message if the inserting went wrong  
     */
    public function insert_mark_for_single_user($new_week_id, $user_id, $is_freezed)
    {
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
     * 
     * Name: check_freezed_user
     * Arguments: $new_week_id (integer id of the current week), 
     *            $user_id (integer id of the user you want to insert mark for), 
     * Return: True if the user going to be freezed
     *         False if the user finished his/her exception period or he/she has not an exception 
     */
    public function check_freezed_user($user_id = 6, $new_week_id = 11)
    {
        //get the duration and starting week id of the exception case if the user has one
        $user_exception = UserException::select('user_exceptions.week_id', 'user_exceptions.duration')
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

        if (!$user_exception) {
            return FALSE;
        }

        $freezed_marks = Mark::select('out_of_100')
            ->where('user_id', $user_id)
            ->where('out_of_100', -1)
            ->where('week_id', '>=', $user_exception->week_id)
            ->where('week_id', '!=', $new_week_id)
            ->get();

        if (count($freezed_marks) == $user_exception->duration) {
            return FALSE;
        } else if (count($freezed_marks) < $user_exception->duration) {
            return TRUE;
        }
        // //add duration to the starting week id to check the duration
        // $duration_from_starting_week = $user_exception->week_id + $user_exception->duration;

        // //subtract the new week id from the 
        // $remaining_duration = $duration_from_starting_week - $new_week_id;
        // $is_freezed = ($remaining_duration > 0 ? TRUE : FALSE);
    }
}