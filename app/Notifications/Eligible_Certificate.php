<?php

namespace App\Notifications;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class Certificate extends Notification implements ShouldQueue
{
    use Queueable;
    protected $pageUrl;

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
            ->subject('أصبوحة || شهادة توثيق الكتاب')
            ->line('تحية طيبة لحضرتك،

هنيئا لك، نبارك لك قراءتك وتعلمك وعملك المميز ونعلمك بأن شهادة توثيق القراءة الخاصة بك جاهزة. ننتظر زيارتك لموقع توثيق القراءة مع أصبوحة 180 للحصول على الشهادة و الاحتفاظ بها على جهازك.')
            ->line('سيكون من الرائع نشرك للشهادة في مواقع التواصل الاجتماعي وذلك لتحفيز المجتمع على القراءة المنهجية المتقدمة.')
            ->line('يوم سعيد نتمناه لك.');
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
