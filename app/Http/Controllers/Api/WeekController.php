<?php

namespace App\Http\Controllers\api;

use App\Exceptions\NotFound;
use App\Http\Controllers\Controller;
use App\Models\Mark;
use App\Models\User;
use App\Models\Week;
use App\Traits\ResponseJson;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
    }

    public function create()
    {
        //get last three weeks 
        $last_week_ids = $this->get_last_weeks_ids();

        $week_id = $this->add_week();

        if ($this->add_marks_for_all_users($week_id, $last_week_ids)) {
            return $this->jsonResponseWithoutMessage('Marks added Successfully', 'data', 200);
        } else {
            return $this->jsonResponseWithoutMessage('Something went wrong, Could not add marks', 'data', 200);
        }
    }

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
    }

    public function searchForWeekTitle($date, $year_weeks)
    {
        foreach ($year_weeks as $val) {
            if ($val['date'] === $date) {
                return $val['title'];
            }
        }
        return null;
    }

    public function add_week()
    {
        // add new week
        $week_id = 0;
        $week = new Week();
        $week->title = $this->searchForWeekTitle(Carbon::now()->startOfWeek(Carbon::SUNDAY)->format('Y-m-d'), YEAR_WEEKS);

        $week->date = Carbon::now()->startOfWeek(Carbon::SUNDAY)->format('Y-m-d');

        if ($week->save()) {
            $week_id = $week->id;
        } else {
            return $this->jsonResponseWithoutMessage('Something went wrong, could not add week', 'data', 500);
        }
        return $week_id;
    }

    public function get_last_weeks_ids()
    {
        //get last three weeks 
        $last_weeks = Week::where('is_vacation', 0)->latest()->limit(3)->get('id');

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

    public function update_excluded_users_and_add_marks($user, $last_week_ids, $week_id)
    {
        $is_freezed = $this->get_freezed_user($user->id);

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
                    //execute the user
                    $user->is_excluded = 1;
                    return $user->save();
                    //check if the user has been freezed in the week before (2nd of last)
                } else if ($marks[1]->out_of_100 === -1) {
                    //check if the user mark is zero in  the week befor (3rd of last)
                    if ($marks[2]->out_of_100 === 0) {
                        //execute the user
                        $user->is_excluded = 1;
                        return $user->save();
                    } else {
                        //add mark
                        return $this->add_mark_for_every_user($week_id, $user->id, $is_freezed);
                    }
                } else {
                    //add mark
                    return $this->add_mark_for_every_user($week_id, $user->id, $is_freezed);
                }
            } else {
                //add mark
                return $this->add_mark_for_every_user($week_id, $user->id, $is_freezed);
            }
        }
        return null;
    }

    public function add_marks_for_all_users($week_id, $last_week_ids)
    {

        // $second_last_week_id = Week::where('date', Carbon::now()->subWeek(2)->startOfWeek(Carbon::SUNDAY)->format('Y-m-d'))->first()->id;
        // $third_last_week_id = Week::where('date', Carbon::now()->subWeek(3)->startOfWeek(Carbon::SUNDAY)->format('Y-m-d'))->first()->id;

        //get all the users and update their records if they are excluded
        $all_users = User::where('is_excluded', 0)->where('is_hold', 0)
            ->whereIn('id', [6, 7, 8, 9, 10, 11, 12]) //for testing - to be deleted
            ->orderBy('id')
            ->chunkByID(100, function ($users) use ($last_week_ids, $week_id) {
                try {
                    //to avoid executing an updated Statement for every single user in our DB
                    DB::beginTransaction();

                    foreach ($users as $user) {
                        //get the mark of the user in the last 3 weeks
                        $this->update_excluded_users_and_add_marks($user, $last_week_ids, $week_id);
                    }

                    DB::commit();
                } catch (\Exception $exception) {
                    Log::error($exception);
                    DB::rollBack();
                }
            }, 'users.id', 'id');

        return $all_users;
    }

    public function add_mark_for_every_user($week_id, $user_id, $is_freezed)
    {
        $mark = new Mark();
        $mark->user_id = $user_id;
        $mark->week_id = $week_id;
        $mark->out_of_90 = ($is_freezed ? 1 : 0);
        $mark->out_of_100 = ($is_freezed ? 1 : 0);
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

    public function get_freezed_user($user_id)
    {
        return 0;
    }
}