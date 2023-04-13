<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\Validator;
use App\Notifications\GeneralNotification;
use Illuminate\Support\Facades\Auth;
use App\Traits\ResponseJson;
use App\Exceptions\NotFound;
use App\Exceptions\NotAuthorized;


class NotificationController extends Controller
{
    use ResponseJson;
    /**
     * Send notification to a specific user by its id with a message and insert it to the database.
     * 
     * @param  $reciver_id , $message
     */
    public function sendNotification($reciver_id , $message) 
    {
        $reciver = User::where('id',$reciver_id)->first();  
        $reciver->notify(new GeneralNotification(Auth::user()->name,$message));
    }
    /**
     * To show all notifications for auth user.
     * 
     * @return jsonResponseWithoutMessage
     */
    public function listAllNotification() 
    {
        $notifications = auth()->user()->notifications()->latest()->limit(20)->get();
    
        if (!$notifications->isEmpty()){
            return $this->jsonResponseWithoutMessage($notifications, 'data',200);
        } else {
            throw new NotFound;
        }
    }
    /**
     * To show unread notifications for auth user.
     * 
     * @return jsonResponseWithoutMessage
     */
    public function listUnreadNotification() 
    {

        //return $this->jsonResponseWithoutMessage(auth()->user()->unreadNotifications()->get(),'data',200);
        $unreadNotifications = auth()->user()->unreadNotifications()->get();

        if (!$unreadNotifications->isEmpty()){
            return $this->jsonResponseWithoutMessage($unreadNotifications,'data',200);
        } else {
            throw new NotFound;

        }
    }
    /**
     * Make specific notification as read by its id.
     * 
     * @return jsonResponseWithoutMessage
     */
    public function markAllNotificationAsRead() 
    {
        $unreadNotifications = auth()->user()->unreadNotifications()->get();
        if (!$unreadNotifications->isEmpty()){
            foreach ($unreadNotifications as $unreadNotification) {
                $unreadNotification->markAsRead();
            }
            return $this->jsonResponseWithoutMessage('Done','data',200);
        } else {
            throw new NotFound;
        }  
    }
    /**
     * Make all notifications as read for the auth user.
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage
     */
    public function markOneNotificationAsRead(Request $request) 
    {
        $validator = Validator::make($request->all(), [
            'notification_id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $notification = auth()->user()->notifications()->where('id', $request->notification_id)->first();
        if (!$notification->isEmpty()) {
            $notification->markAsRead();
            return $this->jsonResponseWithoutMessage('Done','data',200);
        } else {
            throw new NotFound;
        }
    }
}
