<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationsEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $reciver_id;

    public function __construct($message, $reciver_id)
    {
        $this->message = $message;
        $this->reciver_id = $reciver_id;
    }

    public function broadcastOn()
    {
        return new Channel('notifications-channel.' . $this->reciver_id);
    }

    public function broadcastAs()
    {
        return 'new-notifications';
    }
}
