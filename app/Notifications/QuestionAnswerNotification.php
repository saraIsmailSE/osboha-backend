<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuestionAnswerNotification extends Notification implements ShouldQueue
{
    use Queueable;
    protected $sender;
    protected $msg;
    protected $path;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $sender, string $msg, string $path)
    {
        $this->sender = $sender;
        $this->msg = $msg;
        $this->path = $path;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'sender' => $this->sender,
            'message'   =>  $this->msg,
            'type'   =>  'QUESTIONS_ANSWERS',
            'path' => $this->path,
        ];
    }
}
