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
use App\Models\Mark;
use Carbon\Carbon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

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
    use ResponseJson;

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
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $current_week = Week::latest()->first();
        $exception['reason'] = $request->reason;
        $exception['type_id'] =  $request->type_id;
        $exception['user_id'] = Auth::id();

        if ($request->type_id == 1 || $request->type_id == 2) { // تجميد عادي - الاسبوع الحالي أو القادم 
            if (!Auth::user()->hasRole(['leader', 'supervisor', 'advisor', 'admin'])) {
                $laseFreezing = UserException::where(function ($q) {
                    $q->where('type_id', 1)
                        ->orWhere('type_id', 2);
                })->where('user_id', Auth::id())->pluck('week_id')->first();

                if (!$laseFreezing || $laseFreezing + 4 < $current_week->id) {
                    // يوجد تجميد وتعدى 4 اسابيع

                    $exception['status'] = 'accepted';

                    if ($request->type_id == 1) {
                        $thisWeekMark=Mark::where('week_id',$current_week->id)->update(['out_of_100' => -1]);
                        $exception['week_id'] =  $current_week->id;
                        $exception['start_at'] = $current_week->created_at;
                        $exception['end_at'] = Carbon::parse($current_week->created_at->addDays(7))->format('Y-m-d');
                    } else {
                        $exception['week_id'] =  $current_week->id + 1;
                        $exception['start_at'] = Carbon::parse($current_week->created_at->addDays(7))->format('Y-m-d');
                        $exception['end_at'] = Carbon::parse($current_week->created_at->addDays(14))->format('Y-m-d');
                    }

                    $userException = UserException::create($exception);

                    //Notify User
                    $userToNotify = User::find(Auth::id());
                    $userToNotify->notify(new \App\Notifications\FreezException($userException->start_at, $userException->end_at));

                    //Notify Leader
                    $group = UserGroup::where('user_id', Auth::id())->where('user_type', 'ambassador')->pluck('group_id')->first();
                    $leader_id = UserGroup::where('group_id', $group)->where('user_type', 'leader')->pluck('user_id')->first();
                    $msg = "قام السفير " . Auth::user()->name . " باستخدام نظام التجميد";
                    (new NotificationController)->sendNotification($leader_id, $msg);

                    return $this->jsonResponseWithoutMessage("تم رفع طلب التجميد", 'data', 200);
                } else {
                    return $this->jsonResponseWithoutMessage("عذرًا لا يمكنك استخدام نظام التجميد إلا مرة كل 4 أسابيع", 'data', 200);
                }
            } else {
                return $this->jsonResponseWithoutMessage("عذرًا لا يمكنك استخدام نظام التجميد", 'data', 200);
            }
        } elseif ($request->type_id == 3 || $request->type_id == 4) { // نظام امتحانات - شهري أو فصلي  

            $exception['status'] = 'pending';
            $userException = UserException::create($exception);
            //Notify User
            $userToNotify = User::find(Auth::id());
            $userToNotify->notify(new \App\Notifications\ExamException());

            //Notify Leader
            $group = UserGroup::where('user_id', Auth::id())->where('user_type', 'ambassador')->pluck('group_id')->first();
            $leader_id = UserGroup::where('group_id', $group)->where('user_type', 'leader')->pluck('user_id')->first();

            $msg = "قام السفير " . Auth::user()->name . " بطلب نظام امتحانات";
            (new NotificationController)->sendNotification($leader_id, $msg);

            return $this->jsonResponseWithoutMessage("تم رفع طلبك لنظام الامتحانات، انتظر موافقة القائد", 'data', 200);
        } elseif ($request->type_id == 5) { // تجميد استثنائي

            $exception['status'] = 'pending';
            $userException = UserException::create($exception);

            //Notify User
            $userToNotify = User::find(Auth::id());
            $userToNotify->notify(new \App\Notifications\ExceptionalException());

            //Notify Advisor & Leader

            $group = UserGroup::where('user_id', Auth::id())->where('user_type', 'ambassador')->pluck('group_id')->first();
            $advisor_id = UserGroup::where('group_id', $group)->where('user_type', 'advisor')->pluck('user_id')->first();
            $leader_id = UserGroup::where('group_id', $group)->where('user_type', 'leader')->pluck('user_id')->first();

            //Notify Advisor

            $msg = "قام السفير :  " . Auth::user()->name . "بطلب نظام تجميد استثنائي";
            (new NotificationController)->sendNotification($advisor_id, $msg);
            //Notify Leader
            $msg = "قام السفير :  " . Auth::user()->name . "بطلب نظام تجميد استثنائي";
            (new NotificationController)->sendNotification($leader_id, $msg);

            return $this->jsonResponseWithoutMessage("تم رفع طلبك للتجميد الاستثنائي انتظر الموافقة", 'data', 200);
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
                $response['last_freez'] = UserException::where(function ($q) {
                    $q->where('type_id', 1)
                        ->orWhere('type_id', 2);
                })->where('user_id', $userException->user_id)->where(function ($q) {
                    $q->where('status', 'accepted')
                        ->orWhere('status', 'finished');
                })->first();

                //last exam
                $response['last_exam'] = UserException::where(function ($q) {
                    $q->where('type_id', 3)
                        ->orWhere('type_id', 4);
                })->where('user_id', $userException->user_id)->where(function ($q) {
                    $q->where('status', 'accepted')
                        ->orWhere('status', 'finished');
                })->first();
                //last exceptional freez
                $response['last_exceptional_freez'] = UserException::where('type_id', 5)->where('user_id', $userException->user_id)->where(function ($q) {
                    $q->where('status', 'accepted')
                        ->orWhere('status', 'finished');
                })->first();

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
                $userException->status = 'cancelled';
                    $userException->delete();
                    return $this->jsonResponseWithoutMessage("تم لحذف بنجاح", 'data', 200);
            } else {
                throw new NotAuthorized;
            } 
        } else {
            throw new NotFound();
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
     * To reject and accept userException
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
        if ($userException && $userException->status == 'pending' ) {
            if ($userException->type_id == 5) { //exceptional freezing

                // get his advisor
                $group = UserGroup::where('user_id', $userException->user_id)->where('user_type', 'ambassador')->pluck('group_id')->first();
                $advisor_id = UserGroup::where('group_id', $group)->where('user_type', 'advisor')->pluck('user_id')->first();

                if (Auth::id() == $advisor_id || Auth::user()->hasRole('admin')) {

                    $userException->note = $request->note;
                    $userException->reviewer_id = Auth::id();

                    if (in_array($request->decision, [1, 2, 3, 4])) {

                        $current_week = Week::latest()->first();

                        $userException->status = 'accepted';
                        $status = 'مقبول';

                        if ($request->decision == 1) {
                            //اعفاء الأسبوع الحالي
                            $thisWeekMark=Mark::where('week_id',$current_week->id)->update(['out_of_100' => -1]);
                            $userException->week_id =  $current_week->id;
                            $userException->start_at = $current_week->created_at;
                            $userException->end_at = Carbon::parse($current_week->created_at->addDays(7))->format('Y-m-d');
                        } else if ($request->decision == 2) {
                            //اعفاء الأسبوع القادم
                            $userException->week_id =  $current_week->id + 1;
                            $userException->start_at = Carbon::parse($current_week->created_at->addDays(7))->format('Y-m-d');
                            $userException->end_at = Carbon::parse($current_week->created_at->addDays(14))->format('Y-m-d');
                        } else if ($request->decision == 3) {
                            //اعفاء لأسبوعين الحالي و القادم
                            $thisWeekMark=Mark::where('week_id',$current_week->id)->update(['out_of_100' => -1]);
                            $userException->week_id =  $current_week->id + 1;
                            $userException->start_at = $current_week->created_at;
                            $userException->end_at = Carbon::parse($current_week->created_at->addDays(14))->format('Y-m-d');
                        } else if ($request->decision == 4) {
                            //اعفاء لثلاثة أسابيع الحالي - القام - الذي يليه
                            $thisWeekMark=Mark::where('week_id',$current_week->id)->update(['out_of_100' => -1]);
                            $userException->week_id =  $current_week->id + 1;
                            $userException->start_at = $current_week->created_at;
                            $userException->end_at = Carbon::parse($current_week->created_at->addDays(21))->format('Y-m-d');
                        }
                        //notify leader
                        $leader_id = UserGroup::where('group_id', $group)->where('user_type', 'leader')->pluck('user_id')->first();
                        $msg = "السفير:  " . Auth::user()->name . " تحت التجميد الاستثنائي لغاية:  " . $userException->end_at;
                        (new NotificationController)->sendNotification($leader_id, $msg);
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
                    (new NotificationController)->sendNotification($userToNotify->id, $msg);

                    return $this->jsonResponseWithoutMessage("تم التعديل بنجاح", 'data', 200);
                } else {
                    throw new NotAuthorized;
                }
            } elseif ($userException->type_id == 3 || $userException->type_id == 4) { // exam exception
                $group_id = UserGroup::where('user_id', $userException->user_id)->where('user_type', 'ambassador')->pluck('group_id')->first();
                $group = Group::where('id', $group_id)->with('groupAdministrators')->first();

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
                            $userException->week_id =  $current_week->id + 1;
                            $userException->start_at = Carbon::parse($current_week->created_at->addDays(7))->format('Y-m-d');
                            $userException->end_at = Carbon::parse($current_week->created_at->addDays(14))->format('Y-m-d');
                        } else if ($request->decision == 3) {
                            //اعفاء لأسبوعين الحالي و القادم
                            $userException->week_id =  $current_week->id + 1;
                            $userException->start_at = $current_week->created_at;
                            $userException->end_at = Carbon::parse($current_week->created_at->addDays(14))->format('Y-m-d');
                        }

                        //notify leader
                        $leader_id = UserGroup::where('group_id', $group_id)->where('user_type', 'leader')->pluck('user_id')->first();
                        $msg = "السفير:  " . Auth::user()->name . " تحت نظام الامتحانات لغاية:  " . $userException->end_at;
                        (new NotificationController)->sendNotification($leader_id, $msg);
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
                    (new NotificationController)->sendNotification($userToNotify->id, $msg);

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

                $msg = "You have exception vacation until " . $userException->end_at;
                (new NotificationController)->sendNotification($user, $msg);

                return $this->jsonResponseWithoutMessage('User Exception created', 'data', 200);
            } else {
                throw new NotFound();
            }
        } else {
            throw new NotAuthorized;
        }
    }

    public function finisfedException()
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
}
