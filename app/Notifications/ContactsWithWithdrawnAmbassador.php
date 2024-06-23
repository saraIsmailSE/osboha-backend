<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContactsWithWithdrawnAmbassador extends Notification implements ShouldQueue
{
    use Queueable;
    protected $url;
    protected $body;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($body)
    {
        $this->body = $body;
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
            ->from('backreader@osboha180.com', 'Osboha 180')
            ->subject('ننتظرك معنا في "أصبوحة 180" مجدداً')
            ->line('السلام عليكم ورحمة الله وبركاته،')
            ->line('أتمنى أن تكون بخير وفي أتم الصحة والعافية.')
            ->line('معك فريق الاهتمام بالعائدين في مشروع "أصبوحة 180".')
            ->line($this->body)
            ->line('بانتظار ردك على هذا البريد الالكتروني وقرار عودتك بفارغ الصبر.')
            ->line('دمت بخير🌸،')
            ->line('فريق الاهتمام بالعائدين واستعادة المنسحبين');
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
