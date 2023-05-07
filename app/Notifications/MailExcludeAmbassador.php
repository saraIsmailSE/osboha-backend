<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MailExcludeAmbassador extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
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
            ->subject('أصبوحة 180 || استبعاد')
            ->line('تحية طيبة لحضرتك.')
            ->line('')
            ->line('')
            ->line("يؤسفنا إعلامك أنه تم استبعادك من مشروع أصبوحة;")
            ->line("وذلك لحصولك على صفرين متتالين.")
            ->line('بإمكانك العودة متى شئت وكنت على استعداد لمتابعة القراءة')
            ->line('')
            ->line('كل التوفيق والسداد في خطواتك،')
            ->action('أصبوحة 180', 'https://www.platform.osboha180.com');
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