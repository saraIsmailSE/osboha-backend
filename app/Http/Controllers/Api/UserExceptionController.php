<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserException as UserExceptionResource;
use App\Models\UserException;
use App\Models\User;
use App\Models\Group;
use Illuminate\Http\Request;
use App\Traits\ResponseJson;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * UserExceptionController to create exception for user
 *
 * Methods:CRU only
 */

class UserExceptionController extends Controller
{
    use ResponseJson;

    /**
     * Display a listing of user exceptions
     *
     * @return \Illuminate\Http\Response
     */
    
    public function index()
    { 
      $id=Auth::id();
      $user=User::with('Group')->find($id);  
    
      if($user){
        //store user details in single array
        foreach($user->group as $group){
            $data=[
                'user_id'=> $group->pivot->user_id,
                'group_id'=> $group->pivot->group_id,
                'user_type'=> $group->pivot->user_type
            ];
        }

        //if ambassador
        if($data['user_type']=='ambassador'&& Auth::user()->can('list exception')){
            $userException=UserException::all()->where('user_id',$data['user_id']);
        
            return $this->jsonResponseWithoutMessage(
                UserExceptionResource::collection($userException),
                    'data', 200
                  );
        }

        //return all exception by group for specific user type
        if($data['user_type']=='leader' || 'supervisor'||'advisor' && Auth::user()->can('list exception')){
            $user=Group::with('User')->find($data['group_id']); 
            foreach($user->User as $item){
                $ids[]=[
                    'user_id' => $item->id
                ];
            }
            
            $userException=UserException::whereIn('user_id',$ids)->get();

            return $this->jsonResponseWithoutMessage(
                new UserExceptionResource($userException),'data', 200
                );
        }

        else{
            throw new NotAuthorized;
        }
        
       }//end if user found

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
        
        if(Auth::check()){
            $input['user_id']= Auth::id();
            $userException= UserException::create($input);
            return $this->jsonResponseWithoutMessage("User Exception Craeted", 'data', 200);
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

        $userException=UserException::where('user_id',Auth::id())->find($request->exception_id);
        
        if($userException){ 
        return $this->jsonResponseWithoutMessage(new UserExceptionResource($userException),'data', 200);
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
}

