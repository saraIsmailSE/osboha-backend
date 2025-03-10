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
use App\Events\NotificationsEvent;
use App\Models\Week;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        if (!defined('SUPPORT_MARK')) define('SUPPORT_MARK', 'support_mark');
        if (!defined('AUDIT_MARKS')) define('AUDIT_MARKS', 'audit_marks');
        if (!defined('MARKS')) define('MARKS', 'marks');

        if (!defined('ANNOUNCEMENT')) define('ANNOUNCEMENT', 'announcement');
        if (!defined('SUPPORT')) define('SUPPORT', 'support');
    }
    /**
     * Send notification to a specific user by its id with a message and insert it to the database.
     *
     * @param  $receiver_id , $message
     */
    public function sendNotification($receiver_id, $message, $type, $path = null, $delay = null)
    {
        $sender = User::find(Auth::id()) ?? User::find(1);
        $receiver = User::where('id', $receiver_id)->first();
        // $receiver->notify(new GeneralNotification($sender, $message, $type, $path));


        if ($delay) {
            $receiver->notify((new GeneralNotification($sender, $message, $type, $path))->delay($delay));
        } else {
            $receiver->notify(new GeneralNotification($sender, $message, $type, $path));
        }
        try {
            event(new NotificationsEvent($message, $receiver_id));
        } catch (\Exception $e) {
            Log::channel('NotificationBroadcasting')->error('Broadcasting failed: ' . $e->getMessage());
        }
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

        /**
         * @todo: slow query - asmaa
         */
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

    public function deleteOldNotifications()
    {
        try {
            $previous_week = Week::orderBy('created_at', 'desc')->skip(1)->take(2)->first();
            DB::table('notifications')->where('created_at', '<', $previous_week->created_at)->delete();
            Log::channel('Notification')->info('old notifications deleted successfully');
        } catch (\Exception $e) {
            Log::channel('Notification')->error('delete old notifications: ' . $e->getMessage());
        }
    }
}
