<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserException;
use Illuminate\Http\Request;
use App\Traits\ResponseJson;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

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
        $userException= UserException::all()->where('user_id', Auth::id());

        if($userException){
         return $this->jsonResponseWithoutMessage($userException,'data', 200);
        }
        else{
            // throw new NotFound;
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
        
        return $this->jsonResponse($userException,'data', 200, 'User Exception Created');
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
        return $this->jsonResponseWithoutMessage($userException,'data', 200);
        }

        else{
            // throw new NotFound;
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

        $input['user_id']= Auth::id();
        $userException= UserException::find($request->exception_id);
        $userException->update($input);

        return $this->jsonResponse($userException,'data', 200, 'User Exception Updated');
    }

}