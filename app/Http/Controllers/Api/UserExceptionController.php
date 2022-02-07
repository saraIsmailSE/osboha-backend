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
 * Methods:
 *  - CRU only
 *  - Rules for validation
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

        return $this->jsonResponseWithoutMessage($userException,'data', 200);
    }

    /**
     * Create new exception for the user
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rules=$this->rules();
        $input=$request->all();
        $validator= Validator::make($input, $rules);
        
        if($validator->fails()){
          return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 422);
        }

        $input['user_id']= Auth::id();
        $userException= UserException::create($input);
        
        return $this->jsonResponse($userException,'data', 200, 'User Exception Created');
    }

    /**
     * Display the details of specified user exception
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $userException= UserException::findOrFail($request->id);
 
        return $this->jsonResponseWithoutMessage($userException,'data', 200);
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
        $rules= $this->rules();
        $input=$request->all();
        $validator= Validator::make($input, $rules);

        if($validator->fails()){
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 422);
          }

        $input['user_id']= Auth::id();
        $userException= UserException::findOrFail($request->id);
        $userException->update($input);

        return $this->jsonResponse($userException,'data', 200, 'User Exception Updated');
    }

      /**
     *
     * Method for validation rules
     * used by store() and update()
     */
    public function rules(){
        return [
         'week_id' => 'required',
         'reason' => 'required|string',
         'type' => 'required|string',
         'duration' => 'required|int',
         'start_at' => 'required|date',
         'leader_note' => 'nullable|string',
         'advisor_note' => 'nullable|string',
        ];
    }

}