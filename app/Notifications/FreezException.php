<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FreezException extends Notification implements ShouldQueue
{
    use Queueable;
    protected $start_at;
    protected $end_at;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($start_at, $end_at)
    {
        $this->start_at = $start_at;
        $this->end_at = $end_at;
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
            ->line('تم رفع طلبك للتجميد')
            ->line('يبدأ بتاريخ: ' . $this->start_at)
            ->line('ينتهي بتاريخ: ' . $this->end_at)
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