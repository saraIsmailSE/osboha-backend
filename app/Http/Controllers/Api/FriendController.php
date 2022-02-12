<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Friend;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Traits\ResponseJson;
class FriendController extends Controller

{
    use ResponseJson;
public function index()
{
    $friend = Friend::all();
    if($friend){
        return $this->jsonResponseWithoutMessage($friend, 'data',200);
    }
    else{
       // throw new NotFound;
    }
}

public function create(Request $request){

    $validator = Validator::make($request->all(), [
        'user_id' => 'required',
    ]);

    if ($validator->fails()) {
        return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
    }    
        Friend::create($request->all());
        return $this->jsonResponseWithoutMessage("Friend Created Successfully", 'data', 200);
    }

public function show(Request $request)
{
    $validator = Validator::make($request->all(), [
        'friend_id' => 'required',
    ]);

    if ($validator->fails()) {
        return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
    }    

    $friend = Friend::find($request->friend_id);
    if($friend){
        return $this->jsonResponseWithoutMessage($friend, 'data',200);
    }
    else{
       // throw new NotFound;
    }
}

public function update(Request $request)
{
    $validator = Validator::make($request->all(), [
        'friend_id' => 'required',
        'user_id' => 'required',
    ]);

    if ($validator-> status == false) {
        return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
    }
    

    $friend = Friend::find($request->friend_id);
    $friend->update($request->all());
    return $this->jsonResponseWithoutMessage("Friend status Updated Successfully", 'data', 200);
}

public function delete(Request $request)
{
    $validator = Validator::make($request->all(), [
        'friend_id' => 'required',
    ]);

    if ($validator->fails()) {
        return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
    }  

        $friend = Friend::find($request->friend_id);
        $friend->delete();
        return $this->jsonResponseWithoutMessage("friend Deleted Successfully", 'data', 200);
    }
}