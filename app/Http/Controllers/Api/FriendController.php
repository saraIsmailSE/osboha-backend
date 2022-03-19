<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\NotificationController;
use Illuminate\Http\Request;
use App\Models\Friend;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Traits\ResponseJson;
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Http\Resources\FriendResource;
use App\Notifications\FriendRelationshipNotification;


class FriendController extends Controller
{   
    use ResponseJson;
    
    public function index()
    {
        $friends = Friend::where('user_id', Auth::id());
        if ($friends) {
            return $this->jsonResponseWithoutMessage($friends, 'data', 200);
            //return $this->jsonResponseWithoutMessage(FriendResource::collection($friends), 'data', 200);
        } 
        else {
            throw new NotFound;
        }
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'friend_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $input = $request->all();
        $input['user_id'] = Auth::id();
        Friend::create($input);

        $msg = "You have new friend request";
        (new NotificationController)->sendNotification($request->friend_id , $msg);


        return $this->jsonResponseWithoutMessage("Friendship Created Successfully", 'data', 200);
    }

    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'friendship_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $friend = Friend::find($request->Friendship);
        if ($friend) {
            return $this->jsonResponseWithoutMessage($friend, 'data', 200);
            //return $this->jsonResponseWithoutMessage(new FriendResource($friend), 'data', 200);
        } else {
            throw new NotFound;
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'friendship_id' => 'required',
            'status' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }    

        $friendship = Friend::find($request->friendship_id);
        if($friendship){
            if(Auth::id() == $friendship->user_id){
                $friendship->update($request->all());
                return $this->jsonResponseWithoutMessage("Friendship Updated Successfully", 'data', 200);
            }
            else{
                throw new NotAuthorized;   
            }
        }
        else{
            throw new NotFound;   
        }
    
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'friendship_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $friendship = Friend::find($request->friendship_id);
        if ($friendship) {
            if (Auth::id() == $friendship->user_id) {
                $friendship->delete();
                return $this->jsonResponseWithoutMessage("Friendship Deleted Successfully", 'data', 200);
            } else {
                throw new NotAuthorized;
            }
        } 
        else {
            throw new NotFound;
        }
        
        
    }
}
