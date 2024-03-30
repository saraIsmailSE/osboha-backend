<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserException;
use App\Models\Week;
use App\Traits\ResponseJson;
use App\Models\Post;
use App\Models\PostType;
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

    /**
     * Add new week to the system
     * @author Asmaa
     * add new week to the system
     * @return ResponseJson
     */
    public function create()
    {
        //close books and support comments
        $this->closeBooksAndSupportComments();


        DB::beginTransaction();
        try {
            Log::channel('newWeek')->info("begin");

            //add new week to the system
            $newWeekId = $this->insert_week();

            $new_week = Week::find($newWeekId);

            $dateToAdd = new Carbon($new_week->main_timer);
            $new_week->modify_timer = $dateToAdd->addHours(23);
            $new_week->save();

            DB::commit();
            $this->openBooksComments();

            if ($new_week->is_vacation) {
                $this->notifyUsersIsVacation();
            } else {
                $this->notifyUsersNewWeek();
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
    public function insert_week()
    {
        $previousWeek = Week::latest('id')->first();
        $previousDate = $previousWeek ? Carbon::parse($previousWeek->created_at) : null;

        $week = new Week();

        $currentDate = Carbon::now();

        $currentDate->hour = 0;
        $currentDate->minute = 0;
        $currentDate->second = 0;

        $date = $currentDate;

        //check if $date is SUNDAY or not
        if ($currentDate->dayOfWeek != Carbon::SUNDAY) {
            //check if the previous SUNDAY is equal to the previous week date
            $date = $currentDate->previous(Carbon::SUNDAY);
            if ($previousDate && $previousDate->format('Y-m-d') == $date->format('Y-m-d')) {

                $date = $currentDate->next(Carbon::SUNDAY);
            }
        }

        //search sundays
        $dateToSearch = $date;
        $week->title = $this->search_for_week_title($dateToSearch->format('Y-m-d'), config('constants.YEAR_WEEKS'));
        //search is_vacation
        $week->is_vacation = $this->search_for_is_vacation($dateToSearch->format('Y-m-d'), config('constants.YEAR_WEEKS'));

        //add hours to be at 14:00 of SUNDAYS
        $dateToAdd = $date->addHours(14);
        $week->created_at = $dateToAdd;
        $week->updated_at = $dateToAdd;

        //add 7 days to the date to get the end of the week at 13:59
        $week->main_timer = $dateToAdd->addDays(7)->subMinute();
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
                    // $msg = 'إجازة عيد الأضحى المبارك السنوية 🐑';
                    $msg='اجازة عيد الفطر السنوية 🌙';
                    foreach ($users as $user) {
                        $notification->sendNotification($user->id, $msg, NEW_WEEK);
                    }
                } catch (\Exception $e) {
                    throw $e;
                }
            });
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
                $previous_week->modify_timer = $dateToAdd->addDays(4)->addHours(1);
                $previous_week->save();
                Log::channel('newWeek')->info("modify_timer updated Successfully");
            }
        } catch (\Exception $e) {
            Log::channel('newWeek')->info($e);
        }
    }
}
