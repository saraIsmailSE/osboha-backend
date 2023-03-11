<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\NotificationController;
use Illuminate\Http\Request;
use App\Models\Friend;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Traits\ResponseJson;
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Http\Resources\FriendResource;
use Illuminate\Support\Facades\DB;


class FriendController extends Controller
{   
    use ResponseJson;

    /**
     * Return all user`s freinds.
     * @param  $user_id
     * @return jsonResponseWithoutMessage
     */
    public function listByUserId($user_id)
    {
        $user = User::find($user_id);
        $friends=$user->friends()->get();
        $friendsOf=$user->friendsOf()->get();
        $allFriends= $friends->merge($friendsOf);

        return $this->jsonResponseWithoutMessage($allFriends, 'data', 200);
    }
    /**
     * Return all unaccepted user`s freinds.
     * @param  $user_id
     * @return jsonResponseWithoutMessage
     */
    public function listUnAccepted($user_id)
    {
        $user = User::find($user_id);
        $notFriends=$user->notFriends()->get();
        $notFriendsOf=$user->notFriendsOf()->get();
        $allRequests= $notFriends->merge($notFriendsOf);

        return $this->jsonResponseWithoutMessage($allRequests, 'data', 200);
    }
    /**
     * Send freind request if no frienship is exsist.
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage ;
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'friend_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        if (User::where('id',$request->friend_id)->exists()) {
            $friend=$request->friend_id;

            $friendship=Friend::where(function($q) {
                $q->where('user_id', Auth::id())
                ->orWhere('friend_id', Auth::id());
                })
                ->where(function($q) use ($friend){
                    $q->where('user_id',$friend)
                    ->orWhere('friend_id',$friend);
                    })->get();
                if ($friendship->isNotEmpty()) {
                    return $this->jsonResponseWithoutMessage("Friendship already exsits", 'data', 200);

                } else {
                $input = $request->all();
                $input['user_id'] = Auth::id();
                Friend::create($input);

                $msg = "You have new friend request";
                (new NotificationController)->sendNotification($request->friend_id, $msg);
                return $this->jsonResponseWithoutMessage("Friendship Created Successfully", 'data', 200);

                } 
        }
        else{
            return $this->jsonResponseWithoutMessage("user dose not exists", 'data', 200);
        }

    }
    
    /**
     * Find and show an existing frienship in the system by its id.
     *
     * @param  $friendship_id
     * @return jsonResponseWithoutMessage ;
     */
    public function show($friendship_id)
    {

        $friend = Friend::find($friendship_id);
        if ($friend) {
            return $this->jsonResponseWithoutMessage(new FriendResource($friend), 'data', 200);
        } else {
            throw new NotFound;
        }
    }

    /**
     * Accept freind request [only friend_id = Auth can accept].
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage ;
     */

    public function accept(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'friendship_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }    

        $friendship = Friend::find($request->friendship_id);
        if($friendship){
            // to accept user should not be the auther of the relation
            if(Auth::id() != $friendship->user_id || Auth::id() == $friendship->friend_id){
                $friendship->status=1;
                $friendship->save();
                return $this->jsonResponseWithoutMessage("Friend Accepted Successfully", 'data', 200);
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
     * Delete frienship in the system using its id[only friend_id = Auth can delete].
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage ;
     */

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
            if (Auth::id() == $friendship->user_id || Auth::id() == $friendship->friend_id) {
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
