<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserExceptionResource;
use App\Models\UserException;
use App\Models\User;
use App\Models\Group;
use App\Models\UserGroup;
use App\Models\Week;
use Illuminate\Http\Request;
use App\Traits\ResponseJson;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Models\ExceptionType;
use App\Models\Mark;
use App\Traits\PathTrait;
use Carbon\Carbon;

/**
 * UserExceptionController to create exception for user
 *
 * Methods:
 *  - CRU
 *  - revoke: Delete
 *  - getMonth
 * 
 */

class UserExceptionController extends Controller
{
    use ResponseJson, PathTrait;

    /**
     * Add a new user exception to the system.
     * 
     * @param  Request  $request contain exception reason and exception type
     * @return jsonResponseWithoutMessage
     */
    public function create(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'reason' => 'required|string',
            'type_id' => 'required|int',
            'end_at' => 'date|after:yesterday',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $current_week = Week::latest()->first();
        $exception['reason'] = $request->reason;
        $exception['type_id'] =  $request->type_id;
        $exception['user_id'] = Auth::id();

        //get types of exceptions
        $freezCurrentWeek = ExceptionType::where('type', config("constants.FREEZ_THIS_WEEK_TYPE"))->first();
        $freezNextWeek = ExceptionType::where('type', config("constants.FREEZ_NEXT_WEEK_TYPE"))->first();
        $exceptionalFreez = ExceptionType::where('type', config("constants.EXCEPTIONAL_FREEZING_TYPE"))->first();
        $monthlyExam = ExceptionType::where('type', config("constants.EXAMS_MONTHLY_TYPE"))->first();
        $FinalExam = ExceptionType::where('type', config("constants.EXAMS_SEASONAL_TYPE"))->first();

        $group = UserGroup::where('user_id', Auth::id())->where('user_type', 'ambassador')->first()->group;
        $leader_id = Auth::user()->parent_id;

        if ($request->type_id == $freezCurrentWeek->id || $request->type_id == $freezNextWeek->id) { // تجميد عادي - الاسبوع الحالي أو القادم 
            if (!Auth::user()->hasRole(['leader', 'supervisor', 'advisor', 'admin'])) {

                $last4WeeksFreeze = Mark::where('user_id', Auth::id())
                    ->where('created_at', '<', $current_week->created_at)
                    ->limit(4)
                    ->pluck('is_freezed')
                    ->toArray();

                //for new users
                if (count($last4WeeksFreeze) < 4) {
                    return $this->jsonResponseWithoutMessage("عذرًا لا يمكنك استخدام نظام التجميد إلا بعد 4 أسابيع من انضمامك للمشروع", 'data', 200);
                }

                //check if user freezed in last 4 weeks
                foreach ($last4WeeksFreeze as $freezed) {
                    if ($freezed) {
                        return $this->jsonResponseWithoutMessage("عذرًا لا يمكنك استخدام نظام التجميد إلا مرة كل 4 أسابيع", 'data', 200);
                    }
                }

                $currentDate = Carbon::now()->format('Y-m-d');
                $laseFreezing = UserException::where('user_id', Auth::id())
                    ->where('status', config('constants.ACCEPTED_STATUS'))
                    ->whereHas('type', function ($query) {
                        $query->where('type', config('constants.FREEZ_THIS_WEEK_TYPE'))
                            ->orWhere('type', config('constants.FREEZ_NEXT_WEEK_TYPE'));
                    })->pluck('end_at')->first();

                $dateAfter4Weeks = Carbon::parse($laseFreezing)->addWeeks(4)->format('Y-m-d');

                // if (!$laseFreezing || $laseFreezing + 4 < $current_week->id) {
                if (!$laseFreezing ||  $dateAfter4Weeks < $currentDate) {
                    // يوجد تجميد وتعدى 4 اسابيع

                    $exception['status'] = 'accepted';

                    if ($request->type_id == $freezCurrentWeek->id) {
                        Mark::where('week_id', $current_week->id)
                            ->where('user_id', Auth::id())
                            ->update(['reading_mark' => 0, 'writing_mark' => 0, 'total_pages' => 0, 'support' => 0, 'total_thesis' => 0, 'total_screenshot' => 0, 'is_freezed' => 1]);
                        $exception['week_id'] =  $current_week->id;
                        $exception['start_at'] = $current_week->created_at;
                        $exception['end_at'] = Carbon::parse($current_week->created_at->addDays(7))->format('Y-m-d');
                    } else {
                        $exception['week_id'] =  $current_week->id;
                        $exception['start_at'] = Carbon::parse($current_week->created_at->addDays(7))->format('Y-m-d');
                        $exception['end_at'] = Carbon::parse($current_week->created_at->addDays(14))->format('Y-m-d');
                    }

                    $userException = UserException::create($exception);

                    //Notify User
                    $userToNotify = User::find(Auth::id());
                    $userToNotify->notify(new \App\Notifications\FreezException($userException->start_at, $userException->end_at));

                    //Notify Leader                    
                    $msg = "قام السفير " . Auth::user()->name . " باستخدام نظام التجميد";
                    (new NotificationController)->sendNotification($leader_id, $msg, LEADER_EXCEPTIONS, $this->getExceptionPath($userException->id));

                    return $this->jsonResponseWithoutMessage("تم رفع طلب التجميد", 'data', 200);
                } else {
                    return $this->jsonResponseWithoutMessage("عذرًا لا يمكنك استخدام نظام التجميد إلا مرة كل 4 أسابيع", 'data', 200);
                }
            } else {
                return $this->jsonResponseWithoutMessage("عذرًا لا يمكنك استخدام نظام التجميد", 'data', 200);
            }
        } elseif ($request->type_id == $monthlyExam->id || $request->type_id == $FinalExam->id) { // نظام امتحانات - شهري أو فصلي              
            $exception['status'] = 'pending';
            $userException = UserException::create($exception);
            //Notify User
            $userToNotify = User::find(Auth::id());
            $userToNotify->notify(new \App\Notifications\ExamException());

            //Notify Leader            
            $msg = "قام السفير " . Auth::user()->name . " بطلب نظام امتحانات";
            (new NotificationController)->sendNotification($leader_id, $msg, LEADER_EXCEPTIONS, $this->getExceptionPath($userException->id));

            return $this->jsonResponseWithoutMessage("تم رفع طلبك لنظام الامتحانات، انتظر موافقة القائد", 'data', 200);
        } elseif ($request->type_id == $exceptionalFreez->id) { // تجميد استثنائي

            $successMessage = "";
            if (Auth::user()->hasRole('admin')) {
                $exception['status'] = 'accepted';
                $exception['end_at'] = Carbon::parse($request->end_at)->format('Y-m-d');
                $successMessage = "تم تجميدك لغاية " . $exception['end_at'];
            } else {
                $exception['status'] = 'pending';
                $successMessage = "تم رفع طلبك للتجميد الاستثنائي انتظر الموافقة";
            }

            $userException = UserException::create($exception);
            $userException->fresh();

            //Notify User
            $userToNotify = User::find(Auth::id());

            if (Auth::user()->hasRole('admin')) {
                $userToNotify->notify(new \App\Notifications\FreezException($userException->start_at, $userException->end_at));
            } else {

                $userToNotify->notify(new \App\Notifications\ExceptionalException());
            }

            //if not admin
            if (!Auth::user()->hasRole('admin')) {
                //if advisor or consultant, notify admin
                $msg = "قام السفير " . Auth::user()->name . " بطلب نظام تجميد استثنائي";
                if (Auth::user()->hasRole(['advisor', 'supervisor'])) {

                    $admin_id = $group->admin()->first()->id;
                    (new NotificationController)->sendNotification($admin_id, $msg, ADMIN_EXCEPTIONS, $this->getExceptionPath($userException->id));
                } else {
                    //notify advisor & leader                    
                    $advisor_id = $group->groupAdvisor[0]->id;
                    (new NotificationController)->sendNotification($advisor_id, $msg, ADVISOR_EXCEPTIONS, $this->getExceptionPath($userException->id));
                    //Notify Leader
                    (new NotificationController)->sendNotification($leader_id, $msg, LEADER_EXCEPTIONS, $this->getExceptionPath($userException->id));
                }
            }
            return $this->jsonResponseWithoutMessage($successMessage, 'data', 200);
        } else {
            throw new NotFound;
        }
    }

    /**
     * Find an existing user exception in the system by its id display it.
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage
     */
    public function show($exception_id)
    {
        $userException = UserException::find($exception_id);

        if ($userException) {
            if (Auth::id() == $userException->user_id || Auth::user()->hasRole(['leader', 'supervisor', 'advisor', 'admin'])) {
                $group_id = UserGroup::where('user_id', $userException->user_id)->where('user_type', 'ambassador')->pluck('group_id')->first();
                $response['authInGroup'] = UserGroup::where('user_id', Auth::id())->where('group_id', $group_id)->first();
                $response['user_exception'] = $userException;

                //last freez
                $response['last_freez'] = UserException::where('user_id', $userException->user_id)->where(function ($q) {
                    $q->where('status', 'accepted')
                        ->orWhere('status', 'finished');
                })
                    ->whereHas('type', function ($query) {
                        $query->where('type', config('constants.FREEZ_THIS_WEEK_TYPE'))
                            ->orWhere('type', config('constants.FREEZ_NEXT_WEEK_TYPE'));
                    })
                    ->first();

                //last exam
                $response['last_exam'] = UserException::where('user_id', $userException->user_id)->where(function ($q) {
                    $q->where('status', 'accepted')
                        ->orWhere('status', 'finished');
                })
                    ->whereHas('type', function ($query) {
                        $query->where('type', config('constants.EXAMS_MONTHLY_TYPE'))
                            ->orWhere('type', config('constants.EXAMS_SEASONAL_TYPE'));
                    })
                    ->first();
                //last exceptional freez
                $response['last_exceptional_freez'] = UserException::where('user_id', $userException->user_id)->where(function ($q) {
                    $q->where('status', 'accepted')
                        ->orWhere('status', 'finished');
                })
                    ->whereHas('type', function ($query) {
                        $query->where('type', config('constants.EXCEPTIONAL_FREEZING_TYPE'));
                    })
                    ->first();

                return $this->jsonResponseWithoutMessage($response, 'data', 200);
            } else {
                throw new NotAuthorized;
            }
        } //end if $userexception

        else {
            throw new NotFound;
        }
    }

    /**
     * Update an existing user exception’s details by its id( “update exception” permission is required).
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function update(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'exception_id' => 'required',
            'reason' => 'required|string',
            'end_at' => 'required|date|after:yesterday',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $userException = UserException::find($request->exception_id);
        if ($userException) {
            if (Auth::id() == $userException->user_id && $userException->status == 'pending') {
                $input['reason'] = $request->reason;
                $input['end_at'] = Carbon::parse($request->end_at)->format('Y-m-d');
                $userException->update($input);
                return $this->jsonResponseWithoutMessage("User Exception Updated", 'data', 200);
            } else {
                throw new NotAuthorized;
            }
        } else {
            throw new NotFound();
        }
    }

    /**
     * Cancel existing user exception in the system by its id.
     * An exception can be cancelled if: 
     * 1 - The id of auth user matches the user_id for the specified user exception.
     * 2 - exception status is not finished or rejected.
     *
     * @param  $exception_id
     * @return jsonResponseWithoutMessage;
     */
    public function cancelException($exception_id)
    {
        $userException = UserException::find($exception_id);

        if ($userException) {
            if ((Auth::id() == $userException->user_id) && ($userException->status == 'accepted' || $userException->status == 'pending')) {
                $userException->status = 'cancelled';
                $userException->save();
                $current_week = Week::latest()->first();
                Mark::where('week_id', $current_week->id)
                    ->where('user_id', Auth::id())
                    ->update(['is_freezed' => 0]);
                return $this->jsonResponseWithoutMessage("تم الالغاء بنجاح", 'data', 200);
            } else {
                throw new NotAuthorized;
            } //end if Auth
        } else {
            throw new NotFound();
        }
    }

    /**
     * Delete an existing user exception in the system by its id.
     * A user exception can’t be deleted unless: 
     * 1 - The id of auth user matches the user_id for the specified user exception.
     * 2 - exception status is not pending.
     *
     * @param   $exception_id
     * @return jsonResponseWithoutMessage;
     */
    public function delete($exception_id)
    {
        $userException = UserException::find($exception_id);

        if ($userException) {
            if ((Auth::id() == $userException->user_id) && $userException->status == 'pending') {
                $userException->delete();
                return $this->jsonResponseWithoutMessage("تم الحذف بنجاح", 'data', 200);
            } else {
                throw new NotAuthorized;
            }
        } else {
            throw new NotFound;
        }
    }
    /**
     * return the current month.
     *
     * @return currentMonth;
     */
    public function getMonth()
    {
        $currentMonth = Carbon::now();
        return $currentMonth->month;
    }

    /**
     * Accept and Reject Exceptions
     * This action affects Marks
     * 
     * @param $exception_id, Request  $request contains decision
     * @return jsonResponseWithoutMessage;
     */

    public function updateStatus($exception_id, Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'decision' => 'required',
            'note' => 'string',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $userException = UserException::find($exception_id);
        if ($userException && $userException->status == 'pending') {

            //get types of exceptions
            $exceptionalFreez = ExceptionType::where('type', config('constants.EXCEPTIONAL_FREEZING_TYPE'))->first();
            $monthlyExam = ExceptionType::where('type', config('constants.EXAMS_MONTHLY_TYPE'))->first();
            $FinalExam = ExceptionType::where('type', config('constants.EXAMS_SEASONAL_TYPE'))->first();

            $owner_of_exception=User::find($userException->user_id);
            $user_group = UserGroup::with("group")->where('user_id', $userException->user_id)->where('user_type', 'ambassador')->first();
            
            $group = $user_group->group;
            //the head of owner_of_exception
            $leader_id = $owner_of_exception->parent_id;
            $advisor_id = $group->groupAdvisor[0]->id;

            if ($userException->type_id == $exceptionalFreez->id) { //exceptional freezing
                if (Auth::id() == $advisor_id || Auth::user()->hasRole('admin')) {

                    $userException->note = $request->note;
                    $userException->reviewer_id = Auth::id();

                    if (in_array($request->decision, [1, 2, 3, 4])) {

                        $current_week = Week::latest()->first();

                        $userException->status = 'accepted';
                        $status = 'مقبول';

                        if ($request->decision == 1) {
                            //اعفاء الأسبوع الحالي
                            Mark::where('week_id', $current_week->id)
                                ->where('user_id', Auth::id())
                                ->update(['reading_mark' => 0, 'writing_mark' => 0, 'total_pages' => 0, 'support' => 0, 'total_thesis' => 0, 'total_screenshot' => 0, 'is_freezed' => 1]);
                            $userException->week_id =  $current_week->id;
                            $userException->start_at = $current_week->created_at;
                            $userException->end_at = Carbon::parse($current_week->created_at->addDays(7))->format('Y-m-d');
                        } else if ($request->decision == 2) {
                            //اعفاء الأسبوع القادم
                            $userException->week_id =  $current_week->id;
                            $userException->start_at = Carbon::parse($current_week->created_at->addDays(7))->format('Y-m-d');
                            $userException->end_at = Carbon::parse($current_week->created_at->addDays(14))->format('Y-m-d');
                        } else if ($request->decision == 3) {
                            //اعفاء لأسبوعين الحالي و القادم
                            Mark::where('week_id', $current_week->id)
                                ->where('user_id', Auth::id())
                                ->update(['reading_mark' => 0, 'writing_mark' => 0, 'total_pages' => 0, 'support' => 0, 'total_thesis' => 0, 'total_screenshot' => 0, 'is_freezed' => 1]);
                            $userException->week_id =  $current_week->id;
                            $userException->start_at = $current_week->created_at;
                            $userException->end_at = Carbon::parse($current_week->created_at->addDays(14))->format('Y-m-d');
                        } else if ($request->decision == 4) {
                            //اعفاء لثلاثة أسابيع الحالي - القام - الذي يليه
                            Mark::where('week_id', $current_week->id)
                                ->where('user_id', Auth::id())
                                ->update(['reading_mark' => 0, 'writing_mark' => 0, 'total_pages' => 0, 'support' => 0, 'total_thesis' => 0, 'total_screenshot' => 0, 'is_freezed' => 1]);
                            $userException->week_id =  $current_week->id;
                            $userException->start_at = $current_week->created_at;
                            $userException->end_at = Carbon::parse($current_week->created_at->addDays(21))->format('Y-m-d');
                        }
                        //notify leader
                        $msg = "السفير:  " . Auth::user()->name . " تحت التجميد الاستثنائي لغاية:  " . $userException->end_at;
                        (new NotificationController)->sendNotification($leader_id, $msg, LEADER_EXCEPTIONS, $this->getExceptionPath($userException->id));
                    } else {
                        // رفض
                        $userException->status = 'rejected';
                        $status = 'مرفوض';
                    }

                    //update
                    $userException->update();

                    //notify ambassador
                    $userToNotify = User::find($userException->user_id);
                    $userToNotify->notify(
                        (new \App\Notifications\UpdateExceptionStatus($status, $userException->note, $userException->start_at, $userException->end_at))
                            ->delay(now()->addMinutes(2))
                    );

                    $msg = "حالة طلبك للتجميد الاستثنائي هي " . $status;
                    (new NotificationController)->sendNotification($userToNotify->id, $msg, USER_EXCEPTIONS, $this->getExceptionPath($userException->id));

                    return $this->jsonResponseWithoutMessage("تم التعديل بنجاح", 'data', 200);
                } else {
                    throw new NotAuthorized;
                }
            } elseif ($userException->type_id == $monthlyExam->id || $userException->type_id == $FinalExam->id) { // exam exception                
                if (in_array(Auth::id(), $group->groupAdministrators->pluck('id')->toArray())) {
                    $userException->note = $request->note;
                    $userException->reviewer_id = Auth::id();

                    if (in_array($request->decision, [1, 2, 3])) {

                        $current_week = Week::latest()->first();

                        $userException->status = 'accepted';
                        $status = 'مقبول';

                        if ($request->decision == 1) {
                            //اعفاء الأسبوع الحالي
                            $userException->week_id =  $current_week->id;
                            $userException->start_at = $current_week->created_at;
                            $userException->end_at = Carbon::parse($current_week->created_at->addDays(7))->format('Y-m-d');
                        } else if ($request->decision == 2) {
                            //اعفاء الأسبوع القادم
                            $userException->week_id =  $current_week->id;
                            $userException->start_at = Carbon::parse($current_week->created_at->addDays(7))->format('Y-m-d');
                            $userException->end_at = Carbon::parse($current_week->created_at->addDays(14))->format('Y-m-d');
                        } else if ($request->decision == 3) {
                            //اعفاء لأسبوعين الحالي و القادم
                            $userException->week_id =  $current_week->id;
                            $userException->start_at = $current_week->created_at;
                            $userException->end_at = Carbon::parse($current_week->created_at->addDays(14))->format('Y-m-d');
                        }

                        //notify leader                        
                        $msg = "السفير:  " . Auth::user()->name . " تحت نظام الامتحانات لغاية:  " . $userException->end_at;
                        (new NotificationController)->sendNotification($leader_id, $msg, LEADER_EXCEPTIONS, $this->getExceptionPath($userException->id));
                    } else {
                        // رفض
                        $userException->status = 'rejected';
                        $status = 'مرفوض';
                    }

                    //update
                    $userException->update();

                    //notify ambassador
                    $userToNotify = User::find($userException->user_id);
                    $userToNotify->notify(new \App\Notifications\UpdateExceptionStatus($status, $userException->note, $userException->start_at, $userException->end_at));

                    $msg = "حالة طلبك لنظام الامتحانات هي " . $status;
                    (new NotificationController)->sendNotification($userToNotify->id, $msg, USER_EXCEPTIONS, $this->getExceptionPath($userException->id));

                    return $this->jsonResponseWithoutMessage("تم التعديل بنجاح", 'data', 200);
                } else {
                    throw new NotAuthorized;
                }
            }
        } else {
            throw new NotFound();
        }
    }

    public function listPindigExceptions()
    {
        if (Auth::user()->can('list pending exception')) {
            $userExceptions = UserException::where('status', 'pending')->get();
            if ($userExceptions) {
                return $this->jsonResponseWithoutMessage(UserExceptionResource::collection($userExceptions), 'data', 200);
            } else {
                throw new NotFound();
            }
        } else {
            throw new NotAuthorized;
        }
    }

    public function addExceptions(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'user_email' => 'required|email',
            'reason' => 'required|string',
            'type_id' => 'required|int',
            'end_at' => 'required|date|after:yesterday',
            'note' => 'nullable'
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if (Auth::user()->hasRole(['admin', 'advisor'])) {
            $user = User::where('email', $request->user_email)->pluck('id')->first();
            if ($user) {
                $current_week = Week::latest()->pluck('id')->first();
                $input['week_id'] =  $current_week;
                $input['user_id'] = $user;
                $input['status'] = 'accepted';
                $input['reviewer_id'] = Auth::id();
                $input['end_at'] = Carbon::parse($request->end_at)->format('Y-m-d');

                $userException = UserException::create($input);

                $msg = "لديك إعفاء استثنائي لغاية " . $userException->end_at;
                (new NotificationController)->sendNotification($user, $msg, USER_EXCEPTIONS, $this->getExceptionPath($userException->id));

                return $this->jsonResponseWithoutMessage('User Exception created', 'data', 200);
            } else {
                throw new NotFound();
            }
        } else {
            throw new NotAuthorized;
        }
    }

    public function finishedException()
    {
        $userExceptions = UserException::where('status', 'accepted')->whereDate('end_at', '<', Carbon::now())->get();
        if (!$userExceptions->isEmpty()) {
            foreach ($userExceptions as $userException) {
                $userException['status'] = 'finished';
                $userException->update();
            }
            return $this->jsonResponseWithoutMessage('Done', 'data', 200);
        } else {
            return $this->jsonResponseWithoutMessage('all exception are alrady finished', 'data', 200);
        }
    }

    /**
     * List all exceptions for one user
     *
     * @param $user_id
     * @return jsonResponseWithoutMessage
     */

    public function userExceptions($user_id)
    {

            $response['week'] = Week::latest()->first();
            $response['exceptions'] = UserException::where('user_id', $user_id)->latest()->get();
            return $this->jsonResponseWithoutMessage($response, 'data', 200);
    }

    /**
     * Filter user exceptions.
     * 
     * @param  exception filter , user_id
     * @return jsonResponseWithoutMessage
     */
    public function exceptionsFilter($filter, $user_id)
    {

        if ($filter == 'oldest') {
            $exceptions = UserException::where('user_id', $user_id)->get();
        } else if ($filter == 'latest') {
            $exceptions = UserException::where('user_id', $user_id)->latest()->get();
        } else if ($filter == 'freez') {
            $exceptions = UserException::whereHas('type', function ($query) {
                $query->where('type', 'تجميد الأسبوع الحالي')
                    ->orWhere('type', 'تجميد الأسبوع القادم');
            })->where('user_id', $user_id)->latest()->get();
        } else if ($filter == 'exceptional_freez') {
            $exceptions = UserException::whereHas('type', function ($query) {
                $query->where('type', 'تجميد استثنائي');
            })->where('user_id', $user_id)->latest()->get();
        } else if ($filter == 'exams') {
            $exceptions = UserException::whereHas('type', function ($query) {
                $query->where('type', 'نظام امتحانات - شهري')
                    ->orWhere('type', 'نظام امتحانات - فصلي');
            })->where('user_id', $user_id)->latest()->get();
        } else if ($filter == 'accepted') {
            $exceptions = UserException::where('status', 'accepted')->where('user_id', $user_id)->latest()->get();
        } else if ($filter == 'pending') {
            $exceptions = UserException::where('status', 'pending')->where('user_id', $user_id)->latest()->get();
        } else if ($filter == 'rejected') {
            $exceptions = UserException::where('status', 'rejected')->where('user_id', $user_id)->latest()->get();
        } else if ($filter == 'finished') {
            $exceptions = UserException::where('status', 'finished')->where('user_id', $user_id)->latest()->get();
        }

        return $this->jsonResponseWithoutMessage($exceptions, 'data', 200);
    }
}
