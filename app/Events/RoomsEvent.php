<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoomsEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $rooms;
    public $user;
    public $unreadMessages;

    public function __construct($rooms,$unreadMessages, $user)
    {

        $this->rooms = $rooms;
        $this->unreadMessages=$unreadMessages;
        $this->user = $user;
    }

    public function broadcastOn()
    {
        return new Channel('rooms-channel.' . $this->user->id);
    }

    public function broadcastAs()
    {
        return 'new-messages';
    }
}
