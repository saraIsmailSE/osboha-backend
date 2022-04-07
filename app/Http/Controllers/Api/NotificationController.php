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

    /*  function to send notefication 
        take twon parameter: reciver_id & message
    */
    public function sendNotification($reciver_id , $message) 
    {
        $reciver = User::where('id',$reciver_id)->first();  
        $sender = User::where('id',Auth::id())->first();  
        $reciver->notify(new GeneralNotification($sender,$message));
    }

    public function listAllNotification() 
    {
        $notifications = auth()->user()->notifications()->latest()->limit(20)->get();
    
        if (!empty($notifications["data"])){
            return $this->jsonResponseWithoutMessage($notifications, 'data',200);
        } else {
            throw new NotFound;
        }
    }

    public function listUnreadNotification() 
    {
        $unreadNotifications = auth()->user()->unreadNotifications()->get();

        if (!empty($unreadNotifications["data"])){
            return $this->jsonResponseWithoutMessage($unreadNotifications,'data',200);
        } else {
            throw new NotFound;

        }
    }

    public function markAllNotificationAsRead() 
    {
        $unreadNotifications = auth()->user()->unreadNotifications()->get();
        if (!empty($unreadNotifications["data"])){
            foreach ($unreadNotifications as $unreadNotification) {
                $unreadNotification->markAsRead();
            }
            return $this->jsonResponseWithoutMessage('Done','data',200);
        } else {
            throw new NotFound;
        }  
    }

    public function markOneNotificationAsRead(Request $request) 
    {
        $validator = Validator::make($request->all(), [
            'notification_id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $notification = auth()->user()->notifications()->where('id', $request->notification_id)->first();
        if (!empty($notification["data"])) {
            $notification->markAsRead();
            return $this->jsonResponseWithoutMessage('Done','data',200);
        } else {
            throw new NotFound;
        }
    }
}
