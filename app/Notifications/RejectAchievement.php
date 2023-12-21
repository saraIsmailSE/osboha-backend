<?php

namespace App\Notifications;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RejectAchievement extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
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
        ->subject('أصبوحة || تعديل حالة انجازك')
        ->line('تحية طيبة لحضرتك،
        لقد قام فريق المراجعة والتقييم برفض جواب أو أكثر من الأجوبة التي تم تقديمها في طلبكم لتوثيق القراءة مع أصبوحة 180 وذلك لمخالفاتها للشروط.')
        ->line('لطفا قم بزيارة حسابك في الموقع للتعرف على سبب الرفض والحصول على فرصة تعديل الإجابة وطلب إعادة تقييمها.')
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
