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
use App\Http\Resources\UserInfoResource;
use App\Traits\PathTrait;


class FriendController extends Controller
{
    use ResponseJson, PathTrait;

    /**
     * Return all user`s freinds.
     * @param  $user_id
     * @return jsonResponseWithoutMessage
     */
    public function listByUserId($user_id)
    {
        $user = User::find($user_id);
        $friends = $user->friends()->get();
        $friendsOf = $user->friendsOf()->get();
        $response['allFriends'] = UserInfoResource::collection($friends->merge($friendsOf));


        if ($response['allFriends']) {
            if ($user_id == Auth::id()) {
                $response['friendsOfAuth'] = false;
            } else {
                $authUser = Auth::user();
                //Auth Friends
                $friends = $authUser->friends()->get();
                $friendsOf = $authUser->friendsOf()->get();
                $response['friendsOfAuth'] = $friends->merge($friendsOf)->pluck('id');

                //Auth Not Accepted Friends
                $notFriends = $authUser->notFriends()->get();
                $notFriendsOf = $authUser->notFriendsOf()->get();
                $response['notFriendsOfAuth'] = $notFriends->merge($notFriendsOf)->pluck('id');
            }
            return $this->jsonResponseWithoutMessage($response, 'data', 200);
        }

        return $this->jsonResponseWithoutMessage(null, 'data', 200);
    }
    /**
     * Return all unaccepted user`s freinds.
     * @return jsonResponseWithoutMessage
     */
    public function listUnAccepted()
    {
        $allRequests = Friend::with('user')->where('friend_id', Auth::id())->where('status', 0)->get();
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
        if (User::where('id', $request->friend_id)->exists()) {
            $friend = $request->friend_id;

            $friendship = Friend::where(function ($q) {
                $q->where('user_id', Auth::id())
                    ->orWhere('friend_id', Auth::id());
            })
                ->where(function ($q) use ($friend) {
                    $q->where('user_id', $friend)
                        ->orWhere('friend_id', $friend);
                })->first();
            if ($friendship) {
                return $this->jsonResponseWithoutMessage("Friendship already exsits", 'data', 200);
            } else {
                $input = $request->all();
                $input['user_id'] = Auth::id();
                Friend::create($input);

                $msg = "لقد أرسل إليك " . Auth::user()->name . " طلب صداقة";
                (new NotificationController)->sendNotification($request->friend_id, $msg, FRIENDS, $this->getProfilePath(Auth::id()));
                return $this->jsonResponseWithoutMessage("Friendship Created Successfully", 'data', 200);
            }
        } else {
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
     * Accept friendship in the system using its id[ if Auth is part of friendship].
     *
     * @param  Request  $request contains user_id and friend_id
     * @return jsonResponseWithoutMessage ;
     */

    public function accept($friendship_id)
    {
        $friendship = Friend::find($friendship_id);
        if ($friendship) {
            if (Auth::id() == $friendship->friend_id) {

                $friendship->status = 1;
                $friendship->save();

                $msg = "وافق " . Auth::user()->name . " على طلب صداقتك";
                (new NotificationController)->sendNotification($friendship->user_id, $msg, FRIENDS, $this->getProfilePath(Auth::id()));
                return $this->jsonResponseWithoutMessage("Friend Accepted Successfully", 'data', 200);
            } else {
                throw new NotAuthorized;
            }
        } else {
            throw new NotFound;
        }
    }


    /**
     * Delete friendship in the system using its id[ if Auth is part of friendship].
     *
     * @param  Request  $request contains user_id and friend_id
     * @return jsonResponseWithoutMessage ;
     */

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'friend_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        if (Auth::id() == $request->user_id || Auth::id() == $request->friend_id) {
            $friendship = Friend::where('user_id', $request->user_id)->where('friend_id', $request->friend_id)->first();
            if ($friendship) {
                $friendship->delete();
                return $this->jsonResponseWithoutMessage("Friendship Deleted Successfully", 'data', 200);
            } else {
                throw new NotFound;
            }
        } else {
            throw new NotAuthorized;
        }
    }
}