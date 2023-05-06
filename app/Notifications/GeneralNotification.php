<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GeneralNotification extends Notification
{
    use Queueable;
    protected $user;
    protected $sender;
    protected $msg;
    protected $type;
    protected $path;


    public function __construct($sender, $msg, $type, $path = null)
    {
        $this->sender = $sender;
        $this->msg = $msg;
        $this->type = $type;
        $this->path = $path;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toMail($notifiable)
    {
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'sender' => $this->sender,
            'message'   =>  $this->msg,
            'type'   =>  $this->type,
            'path' => $this->path,
        ];
    }
}