<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserException;
use Illuminate\Http\Request;
use App\Traits\ResponseJson;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\UserException as UserExceptionResources; 

class UserExceptionController extends Controller
{
    use ResponseJson;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    
    public function index()
    {
        //
    }

    /**
     * Create exception for the user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
       
        $input=$request->all();
        $validator=Validator::make($input,[
        'week_id' => 'required',
        'reason' => 'required|string',
        'type' => 'required|string',
        'duration' => 'required|int',
        'status' => 'required|string',
        'start_at' => 'required|date',
        'leader_note' => 'nullable|string',
        'advisor_note' => 'nullable|string',
        ]);
        
        if($validator->fails()){
          return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $input['user_id']= Auth::id();
        $userException= UserException::create($input);

        return $this->jsonResponse(
            new UserExceptionResources($userException),
             'data', 200, 'User Exception Created'
            );

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

}
