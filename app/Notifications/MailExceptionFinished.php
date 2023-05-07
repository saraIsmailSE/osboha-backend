<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MailExceptionFinished extends Notification
{
    use Queueable;
    protected $title;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($title)
    {
        $this->title = $title;
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
            ->subject('أصبوحة 180 || انتهاء ' . $this->title)
            ->line('حياك الله 👋🏻')
            ->line('نرجو أن تكون بخير وعافية.')
            ->line('')
            ->line('نود إعلامك أن فترة إعفاءك الخاصة ب' . $this->title . ' قد انتهت.')
            ->line('')
            ->line('للتذكير؛')
            ->line('سيعود التقييم كما السابق من مهام أو متابعة وعلامتك سيتم حسابها بنفس النظام.')
            ->line('')
            ->line('')
            ->line('جدد النية،')
            ->line('وفقك الله.')
            ->line('')
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