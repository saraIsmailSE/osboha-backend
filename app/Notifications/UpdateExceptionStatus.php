<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UpdateExceptionStatus extends Notification implements ShouldQueue
{
    use Queueable;
    protected $status;
    protected $note;
    protected $start_at;
    protected $end_at;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($status,$note, $start_at, $end_at)
    {
        $this->status=$status;
        $this->note=$note;
        $this->start_at=$start_at;
        $this->end_at=$end_at;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    { 

        return (new MailMessage)
        ->from('no-replay@osboha180.com', 'Osboha 180')
        ->subject('أصبوحة || طلب تجميد')
        ->line('تحية طيبة لحضرتك،')
        ->line('تم تعديل حالة طلبك للاعفاء ')
        ->line('الملاحظات ')
        ->line($this->note)
        ->lineIf($this->status == 'مقبول','يبدأ بـ : '.$this->start_at)
        ->lineIf($this->status == 'مقبول','ينتهي : ' . $this->end_at)
        ->line('لك التحية.');
        
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
            //
        ];
    }

}