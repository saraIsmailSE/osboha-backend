<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WithdrawnAccepted extends Notification implements ShouldQueue
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
        $this->url = 'https://platform.osboha180.com';
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
        ->subject('أصبوحة || قبول طلبك للانسحاب')
        ->line('تحية طيبة لحضرتك،')
        ->line('نأسف لطلبك الانسحاب المؤقت من المشروع، ونأمل أن تتغلب على الظروف التي تواجهك الآن. نحن ندعمك في رحلتك ونتمنى لك النجاح في المستقبل.')
        ->line('لا تتردد في العودة في أي وقت ترغب في الانضمام مرة أخرى إلى مجتمعنا والمساهمة في صناعة التغيير وزيادة الوعي')
        ->action('عودة', $this->url)
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