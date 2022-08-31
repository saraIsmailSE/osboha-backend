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
     * Display a listing of user exceptions
     *
     * @return \Illuminate\Http\Response
     */
    
    public function groupExceptions(Request $request)
    {  
        $input=$request->all();
        $validator= Validator::make($input, [
            'group_id' => 'required|int',
            'status' => 'nullable',
            'period' => 'nullable'
        ]);

        if($validator->fails()){
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $user_type = UserGroup::where('group_id',$request->group_id)
            ->where('user_id',Auth::id())->pluck('user_type')
            ->first();

        if($user_type){

            $userExceptions = UserException::query();
            if (!empty($request->status)) {
                $userExceptions = $userExceptions->where('status', $request->status);
            }
            if (!empty($request->period)) {
                if($request->period == 'lastWeek'){
                    $current_week = Week::latest()->first();

                    $userExceptions = $userExceptions->where('week_id', $current_week->id -1);
                } elseif($request->period == 'lastMonth') {
                    $userExceptions = $userExceptions->whereMonth('created_at',$this->getMonth());
                }
            }

            if($user_type == 'ambassador'){ // ambassador in this group

                $userExceptions = $userExceptions->where('user_id',Auth::id())->get();

            } else { //not ambassador in this group

                $ambassador_id = UserGroup::where('group_id',$request->group_id)
                ->where('user_type','ambassador')
                ->pluck('user_id')->toArray();

                $userExceptions = $userExceptions->whereIn('user_id',$ambassador_id)->get();

            }
            return $this->jsonResponseWithoutMessage(UserExceptionResource::collection($userExceptions), 'data', 200);
            
        } else {
            throw new NotAuthorized;
        }
    }

    /**
     * Create new exception for the user
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $input=$request->all();
        $validator= Validator::make($input, [
            'reason' => 'required|string',
            'type_id' => 'required|int',
            'end_at' => 'required|date|after:yesterday',
        ]);
        
        if($validator->fails()){
          return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $current_week = Week::latest()->first();
        $input['user_id'] = Auth::id();
        $input['week_id'] =  $current_week->id;

        if ($request->type_id == 1){ // freeze 
            if(!Auth::user()->hasRole(['leader','supervisor','advisor','admin'])){
                $laseFreezing = UserException::where('user_id', Auth::id())->where('type_id', 1)->pluck('week_id')->first();
                if (!$laseFreezing || $laseFreezing+4 < $current_week->id) {
                    $input['end_at'] = Carbon::parse($current_week->created_at->addDays(7))->format('Y-m-d');
                    $input['status']='accepted';
                    $userException = UserException::create($input);

                    $group = UserGroup::where('user_id',Auth::id())->where('user_type','ambassador')->pluck('group_id')->first();
                    $leader_id = UserGroup::where('group_id',$group)->where('user_type','leader')->pluck('user_id')->first();
                    $msg = "The ambassador ".Auth::user()->name . " used freezing system for this week";
                    (new NotificationController)->sendNotification($leader_id, $msg);

                    return $this->jsonResponseWithoutMessage("User Exception Created Successfully For One Week", 'data', 200);
                } else {
                    return $this->jsonResponseWithoutMessage("Sorry, You can use freezing system just once each 4 weeks", 'data', 200);

                }
            } else {
                return $this->jsonResponseWithoutMessage("Sorry, You can not use freezing system", 'data', 200);
            }

        } elseif ($request->type_id == 2) { // exceptional freeze

            $input['end_at']=Carbon::parse($request->end_at)->format('Y-m-d');
            $input['status'] = 'pending'; 
            $userException = UserException::create($input);

            $group = UserGroup::where('user_id',Auth::id())->where('user_type','ambassador')->pluck('group_id')->first();
            $advisor_id = UserGroup::where('group_id',$group)->where('user_type','advisor')->pluck('user_id')->first();
            $leader_id = UserGroup::where('group_id',$group)->where('user_type','leader')->pluck('user_id')->first();

            $msg = "You have new user exception request from ".Auth::user()->name;
            (new NotificationController)->sendNotification($advisor_id, $msg);

            $msg = "There is a new freezing exception request from ".Auth::user()->name . "needs advisor approval";
            (new NotificationController)->sendNotification($leader_id, $msg);

            return $this->jsonResponseWithoutMessage("Your Exceptional Freezing request is under review", 'data', 200);

         }  elseif ($request->type_id == 3) { // exams  
                $input['end_at']=Carbon::parse($request->end_at)->format('Y-m-d');
                $input['status'] = 'pending'; 
                $userException = UserException::create($input);
    
                $group = UserGroup::where('user_id',Auth::id())->where('user_type','ambassador')->pluck('group_id')->first();
                $leader_id = UserGroup::where('group_id',$group)->where('user_type','leader')->pluck('user_id')->first();

                $msg = "You have new user exception request from ".Auth::user()->name;
                (new NotificationController)->sendNotification($leader_id, $msg);
    
                return $this->jsonResponseWithoutMessage("Your Exam Exceptional request is under review", 'data', 200);
        } else {
            throw new NotFound;
        }

        
    }
    /**
     * Display the details of specified user exception
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'exception_id' => 'required',
        ]);
        
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $userException = UserException::find($request->exception_id);
        
        if($userException){ 
            if(Auth::id() == $userException->user_id || Auth::user()->hasRole(['leader','supervisor','advisor']))
            {
             return $this->jsonResponseWithoutMessage(new UserExceptionResource($userException),'data', 200);
            }

            else{
                throw new NotAuthorized;
            }
        }//end if $userexception
        
        else{
            throw new NotFound;
         }
    }

    /**
     * Update the specified user exception in database
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $input=$request->all();
        $validator= Validator::make($input, [
            'exception_id' => 'required',
            'reason' => 'required|string',
            'end_at' => 'required|date|after:yesterday',
        ]);

        if($validator->fails()){
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        
        $userException = UserException::find($request->exception_id);
        if($userException){
            if(Auth::id() == $userException->user_id && $userException->status == 'pending'){
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
     * revoke the userexception if still not reviewed by leader and advisor
     */
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'exception_id' => 'required',
        ]);

        if($validator->fails()){
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $userException = UserException::find($request->exception_id);

        if ($userException){
            $current_week = Week::latest()->pluck('id')->first();
            if(Auth::id() == $userException->user_id ){
                if($userException->status == 'pending' || ($userException->week_id == $current_week && $userException->type_id == 1)  ){
                    $userException->delete();
                    return $this->jsonResponseWithoutMessage("User Exception Revoked", 'data', 200);
                } elseif ($userException->status == 'accepted' && $userException->end_at > Carbon::now()){
                    $userException->status = 'cancelled';
                    $userException->update();
                    return $this->jsonResponseWithoutMessage("User Exception Cancelled", 'data', 200);
                } else {
                    return $this->jsonResponseWithoutMessage("You Can not Revoke This Exception", 'data', 200);
                }
            }
            else {
                throw new NotAuthorized;
            }//end if Auth
        }
        else {
            throw new NotFound();
        }
    }

    /**
     * return the current month
     */
    public function getMonth()
    {
        $currentMonth=Carbon::now();
        return $currentMonth->month;
    }

    /**
     * To reject and accept userException
     */
    public function updateStatus(Request $request)
    {   
        $input=$request->all();
        $validator= Validator::make($input, [
            'exception_id' => 'required',
            'status' => 'required',
            'note' => 'nullable|string',
        ]);

        if($validator->fails()){
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $userException = UserException::find($request->exception_id);
        if($userException){
            if($userException->type_id == 2){ //exceptional freezing
                $group = UserGroup::where('user_id',$userException->user_id)->where('user_type','ambassador')->pluck('group_id')->first();
                $advisor_id = UserGroup::where('group_id',$group)->where('user_type','advisor')->pluck('user_id')->first();
                $leader_id = UserGroup::where('group_id',$group)->where('user_type','leader')->pluck('user_id')->first();
                if(Auth::id() == $advisor_id || Auth::user()->can('list pending exception')){
                    $userException['status'] = $request->status;
                    $userException['note'] = $request->note;
                    $userException['reviewer_id'] = Auth::id();
                    $userException->update();
                } else {
                    throw new NotAuthorized;
                }
                //notify leader
                $msg = "The " .Auth::user()->name. " is under Exceptional Freezing until ".$userException->end_at;
                (new NotificationController)->sendNotification($leader_id, $msg);
                //notify ambassador
                $msg = "Your Exceptional Freezing is ". $userException['status'] ;
                (new NotificationController)->sendNotification($userException->user_id, $msg);

                return $this->jsonResponseWithoutMessage("User Exception Updated", 'data', 200);

            } elseif ($userException->type_id == 3) { // exam exception
                $group = UserGroup::where('user_id',$userException->user_id)->where('user_type','ambassador')->pluck('group_id')->first();
                $leader_id = UserGroup::where('group_id',$group)->where('user_type','leader')->pluck('user_id')->first();
                if(Auth::id() == $leader_id || Auth::user()->can('list pending exception')){
                    $userException['status'] = $request->status;
                    $userException['note'] = $request->note;
                    $userException['reviewer_id'] = Auth::id();
                    $userException->update();

                    //notify ambassador
                    $msg = "Your Exam Exception is ". $userException['status'] ;
                    (new NotificationController)->sendNotification($userException->user_id, $msg);

                    return $this->jsonResponseWithoutMessage("UserException Status is Updated", 'data', 200);

                } else {
                    throw new NotAuthorized;
                }
        
            } else {
                return $this->jsonResponseWithoutMessage("You Can not change status for this exception ", 'data', 200);
            }
        } else {
            throw new NotFound();
        }
    } 

    public function listPindigExceptions()
    { 
        if(Auth::user()->can('list pending exception')){
            $userExceptions = UserException::where('status', 'pending')->get();
            if($userExceptions){
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
        $input=$request->all();
        $validator= Validator::make($input, [
            'user_email' => 'required|email',
            'reason' => 'required|string',
            'type_id' => 'required|int',
            'end_at' => 'required|date|after:yesterday',
            'note' => 'nullable'
        ]);

        if($validator->fails()){
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if( Auth::user()->hasRole(['admin','advisor'])){
            $user = User::where('email',$request->user_email)->pluck('id')->first();
            if($user){    
                $current_week = Week::latest()->pluck('id')->first();
                $input['week_id'] =  $current_week;
                $input['user_id'] = $user;
                $input['status'] = 'accepted';
                $input['reviewer_id'] = Auth::id();
                $input['end_at']=Carbon::parse($request->end_at)->format('Y-m-d');

                $userException = UserException::create($input);

                $msg = "You have exception vacation until ". $userException->end_at ;
                (new NotificationController)->sendNotification($user, $msg);

                return $this->jsonResponseWithoutMessage('User Exception created', 'data', 200);
            } else {
                throw new NotFound();
            }
        } else {
            throw new NotAuthorized;
        }
    }
    
}