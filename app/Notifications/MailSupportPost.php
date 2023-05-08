<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MailSupportPost extends Notification
{
    use Queueable;
    protected $url;
    protected $name;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->url = 'https://www.platform.osboha180.com' . '/post/post_id';
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
            ->subject('أصبوحة || منشور اعرف مشروعك')
            ->line('مرحباً بك سفيرنا  ' . $this->name . '،')
            ->line('نرجو أن تكون بأفضل حال')
            ->line('')
            ->line('بعد مراجعة التصويت الخاص بك على منشور 《اعرف مشروعك》؛ تم رفض التصويت لمخالفته للشروط.')
            ->line('فضلًا قم بمراجعة حسابك الخاص في المنصة لمعرفة السبب بشكل أوضح وتعديل الإجابة قبل نهاية الأسبوع. ')
            ->action('رابط المنشور: ', $this->url)
            ->line('')
            ->line('بارك الله قوتك.');
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