<?php

namespace App\Notifications;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
//custom
use Illuminate\Support\Facades\Lang;
use Illuminate\Auth\Notifications\ResetPassword;

class RejectUserEmail extends Notification implements ShouldQueue
{ 
    use Queueable;
    protected $pageUrl;
    protected $rejectNote;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($rejectNote)
    {        $this->rejectNote=$rejectNote;

        $this->pageUrl = 'https://www.eligible.osboha180.com/user/update-info';
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
                ->subject('أصبوحة || رفض التسجيل')
        ->line('مرحبا
        تحية طيبة لحضرتك،
        للأسف لم يتم قبول تسجيلك في موقع توثيق القراءة - أصبوحة 180 بسبب عدم تطابق المعلومات مع الشروط. الرجاء تحديث بياناتك ومراعاة تقديم معلوماتك التي تطابق الوثائق الرسمية.')
        ->line('سبب الرفض ')
        ->line($this->rejectNote)
        ->action('تحديث بياناتي', $this->pageUrl);
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
