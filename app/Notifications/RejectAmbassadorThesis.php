<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RejectAmbassadorThesis extends Notification
{
    use Queueable;
    protected $reason;
    protected $name;
    protected $url;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($name, $reason, $book_id, $thesis_id)
    {
        $this->name = $name;
        $this->reason = $reason;

        $this->url = 'https://www.platform.osboha180.com' . '/book/user-single-thesis/' . $book_id . '/' . $thesis_id;
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
            ->subject('أصبوحة || تعديل علامة أطروحتك')
            ->line('حياك الله سفيرنا ' . $this->name . '،')
            ->line('')
            ->line('بعد مراجعة إنجازك وتدقيقه وجدنا:')
            ->line($this->reason)
            ->line('')
            ->line('وبناء عليه تم تعديل العلامة الخاصة بك.')
            ->line('')
            ->line('نذكِّرك أنه ما دام لم ينتهِ الأسبوع فأمامك فرصة للمشاركة والإضافة. 🌸')
            ->line('')
            ->line('رابط الأطروحة: ' . $this->url)
            ->line('قواكم الله.');
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