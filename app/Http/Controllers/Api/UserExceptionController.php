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
 *  - CRU
 *  - revoke: Delete
 *  - getMonth
 * 
 */

class UserExceptionController extends Controller
{
    use ResponseJson;

    /**
     * Read all exceptions for the ambassador in a group by auth user,
     * oR read all exceptions for members of the group if the auth user has a leader, supervisor or advisor role.
     *
     * @return jsonResponseWithoutMessage
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
        }//end if auth user

        else{
            throw new NotFound;
        }
  }
    /**
     * Add a new user exception to the system.
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage
     */
    public function create(Request $request)
    {
        $input=$request->all();
        $validator= Validator::make($input, [
            'week_id' => 'required',
            'reason' => 'required|string',
            'type_id' => 'required|int',
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
        return $this->jsonResponseWithoutMessage("User Exception Created", 'data', 200);
    }
    /**
     * Find an existing user exception in the system by its id display it.
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage
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
     * Update an existing user exception’s details by its id( “update exception” permission is required).
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function update(Request $request)
    {
        $input=$request->all();
        $validator= Validator::make($input, [
            'exception_id' => 'required',
            'week_id' => 'required',
            'reason' => 'required|string',
            'type_id' => 'required|int',
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

          if($userException){
              $userException->update($input);

              return $this->jsonResponseWithoutMessage("User Exception Updated", 'data', 200);
          }
          else {
              throw new NotFound();
          }
        }
        else {
            throw new NotAuthorized;
        }
    }

     /**
     * Delete an existing user exception in the system by its id.
     * A user exception can’t be deleted unless: 
     * 1 - The id of auth user matches the user_id for the specified user exception.
     * 2 - leader_note is null (meaning the exception hasn’t been reviewed by the leader).
     * 3 - advisor_note is null (meaning the exception hasn’t been reviewed by an advisor).
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'exception_id' => 'required',
        ]);

        if($validator->fails()){
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $userException=UserException::find($request->exception_id);

        if ($userException){
            if(Auth::id() == $userException->user_id){
                if($userException->leader_note== null && $userException->advisor_note == null){
                    $userException->delete();
                    return $this->jsonResponseWithoutMessage("User Exception Revoked", 'data', 200);
                }
                else {
                    throw new NotAuthorized;
                }
            }//end if Auth
            else {
                throw new NotAuthorized;
            }
        }
        else {
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
        $currentMonth=Carbon::now();
        return $currentMonth->month;
    }
}