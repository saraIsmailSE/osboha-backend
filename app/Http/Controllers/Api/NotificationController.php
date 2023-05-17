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
    public function __construct()
    {
        //create constants for notification types
        if (!defined('FRIENDS')) define('FRIENDS', 'friends');

        if (!defined('FRIENDS_REQUESTS')) define('FRIENDS_REQUESTS', 'friends_requests');

        if (!defined('USER_EXCEPTIONS')) define('USER_EXCEPTIONS', 'user_exceptions');

        if (!defined('LEADER_EXCEPTIONS')) define('LEADER_EXCEPTIONS', 'leader_exceptions');

        if (!defined('ADVISOR_EXCEPTIONS')) define('ADVISOR_EXCEPTIONS', 'advisor_exceptions');

        if (!defined('ADMIN_EXCEPTIONS')) define('ADMIN_EXCEPTIONS', 'admin_exceptions');

        if (!defined('GROUPS')) define('GROUPS', 'groups');

        if (!defined('GROUP_POSTS')) define('GROUP_POSTS', 'group_posts');

        if (!defined('USER_POSTS')) define('USER_POSTS', 'user_posts');

        if (!defined('PROFILE_POSTS')) define('PROFILE_POSTS', 'profile_posts');

        if (!defined('ROLES')) define('ROLES', 'roles');

        if (!defined('TAGS')) define('TAGS', 'tags');

        if (!defined('ACHIEVEMENTS')) define('ACHIEVEMENTS', 'achievements');

        if (!defined('NEW_WEEK')) define('NEW_WEEK', 'new_week');

        if (!defined('EXCLUDED_USER')) define('EXCLUDED_USER', 'excluded_user');
    }
    /**
     * Send notification to a specific user by its id with a message and insert it to the database.
     * 
     * @param  $reciver_id , $message
     */
    public function sendNotification($reciver_id, $message, $type, $path = null)
    {
        $sender = User::find(Auth::id()) ?? User::find(1);
        $reciver = User::where('id', $reciver_id)->first();
        $reciver->notify(new GeneralNotification($sender, $message, $type, $path));
    }
    /**
     * To show all notifications for auth user.
     * 
     * @return jsonResponseWithoutMessage
     */
    public function listAllNotification()
    {
        $notifications = auth()->user()->notifications()->paginate(20);

        if (!$notifications->isEmpty()) {
            return $this->jsonResponseWithoutMessage($notifications, 'data', 200);
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
        $unreadNotifications = auth()->user()->unreadNotifications()->get();

        return $this->jsonResponseWithoutMessage($unreadNotifications, 'data', 200);
    }
    /**
     * Make all notifications as read for the auth user.
     * 
     * @return jsonResponseWithoutMessage
     */
    public function markAllAsRead()
    {
        $user = User::find(Auth::id());

        $user->unreadNotifications()->update(['read_at' => now()]);

        return $this->jsonResponseWithoutMessage(auth()->user()->notifications()->paginate(20), 'data', 200);
    }
    /**
     *  Make specific notification as read by its id.
     * @param  Request  $request
     * @return jsonResponseWithoutMessage
     */
    public function markAsRead($notification_id)
    {
        $notification = auth()->user()->notifications()->where('id', $notification_id)->first();
        if ($notification) {
            $notification->markAsRead();
            return $this->jsonResponseWithoutMessage(auth()->user()->notifications()->paginate(20), 'data', 200);
        } else {
            throw new NotFound;
        }
    }
}