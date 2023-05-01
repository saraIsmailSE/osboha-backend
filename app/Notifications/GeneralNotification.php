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
    
    
    public function __construct($sender , $msg, $type)
    {
        $this->sender = $sender;
        $this->msg = $msg;
        $this->type = $type;

    }
   
    public function via($notifiable)
    {
        return ['database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
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
        ];
    }
}
