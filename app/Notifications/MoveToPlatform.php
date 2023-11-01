<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MoveToPlatform extends Notification
{
    use Queueable;
    protected $url;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->url = 'https://www.platform.osboha180.com';
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
            ->subject('منصة أصبوحة 180 || تم تسجيلك')
            ->line('بسم الله الرحمن الرحيم ')
            ->line('تحية طيبة وبعد ')
            ->line('')
            ->line('لمشاركتك مسبقا في موقع توثيق الكتب،  تم عمل حساب لك في منصة أصبوحة 180 وذلك لأننا قمنا بدمج موقع التوثيق في منصتنا.')
            ->line('')
            ->line('بانتظارك بين القراء')
            ->action('رابط المنصة', $this->url);

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
