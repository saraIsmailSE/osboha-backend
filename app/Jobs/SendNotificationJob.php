<?php

namespace App\Jobs;

use App\Http\Controllers\Api\NotificationController;
use App\Models\User;
use App\Notifications\GeneralNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $receiverId;
    protected $message;
    protected $type;
    protected $path;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($receiverId, $message, $type, $path = null)
    {
        $this->receiverId = $receiverId;
        $this->message = $message;
        $this->type = $type;
        $this->path = $path;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $notification = new  NotificationController();
        $notification->sendNotification($this->receiverId, $this->message, $this->type, $this->path);
    }
}
