<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserExceptionResource;
use App\Models\UserException;
use App\Models\User;
use App\Models\Group;
use App\Models\UserGroup;
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
 *  - CRU only
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
    
    public function index(Request $request)
    { 
        
        $user=UserGroup::where('group_id',$request->group_id)
            ->where('user_id',Auth::id())
            ->first();

        if($user){

         if($user->user_type == 'ambassador'){
            $userException=UserException::where('user_id',Auth::id())
                            ->whereMonth('created_at',$this->getMonth())
                            ->get();

                return $this->jsonResponseWithoutMessage(
                        UserExceptionResource::collection($userException),
                            'data', 200
                            );
            }

         if(Auth::user()->hasRole(['leader','supervisor','advisor'])){

            $group=Group::with('User')->find($request->group_id);

            if($user->user_type!="ambassador"){
                foreach($group->User as $item){
                    $ids[]=[
                        'user_id' => $item->id
                    ];
                }
        
                $userException=UserException::whereIn('user_id',$ids)
                            ->whereMonth('created_at',$this->getMonth())
                            ->get();
                
                return $this->jsonResponseWithoutMessage(
                        UserExceptionResource::collection($userException),
                            'data', 200
                            );
        
            }//end if not ambassador
            else{
                throw new NotAuthorized;
            }
        }//end if hasRole

        else{
            throw new NotAuthorized;
        }
        }//end if $user

        else{
            throw new NotFound;
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
            'week_id' => 'required',
            'reason' => 'required|string',
            'type' => 'required|string',
            'duration' => 'required|int',
            'start_at' => 'required|date',
            'leader_note' => 'nullable|string',
            'advisor_note' => 'nullable|string',
        ]);
        
        if($validator->fails()){
          return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        
    
        $input['user_id']= Auth::id();
        $userException= UserException::create($input);
        return $this->jsonResponseWithoutMessage("User Exception Craeted", 'data', 200);
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

        $userException=UserException::find($request->exception_id);
        
        if($userException){ 
            if(Auth::id() == $userException->user_id || Auth::user()->hasRole(['leader','supervisor','advisor'])){
             return $this->jsonResponseWithoutMessage(new UserExceptionResource($userException),'data', 200);
            }

            else{
                throw new NotAuthorized;
            }
        }
        
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
            'week_id' => 'required',
            'reason' => 'required|string',
            'type' => 'required|string',
            'duration' => 'required|int',
            'start_at' => 'required|date',
            'leader_note' => 'nullable|string',
            'advisor_note' => 'nullable|string',
        ]);

        if($validator->fails()){
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
          }

        if(Auth::user()->can('update exception'))
        {
          $input['user_id']= Auth::id();
          $userException= UserException::find($request->exception_id);
          $userException->update($input);

          return $this->jsonResponseWithoutMessage("User Exception Updated", 'data', 200);
        }

        else {
            throw new NotAuthorized;
        }
    }


    /**
     * revoke the userexception if its not approved
     */
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'exception_id' => 'required',
        ]);

        $userException=UserException::find($request->exception_id);
     
        if(Auth::id() == $userException->user_id){
            if($userException->leader_note== null ||$userException->advisor_note == null){
                $userException->delete();
                return $this->jsonResponseWithoutMessage("User Exception Revoked", 'data', 200);
            }

            if(Auth::user()->hasRole(['leader','supervisor','advisor'])){
                $userException->delete();
                return $this->jsonResponseWithoutMessage("User Exception Revoked", 'data', 200); 
            }
    
            else{
                throw new NotAuthorized;
            }
        }//end if Auth

        else{
            throw new NotAuthorized;
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
}

