<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MailDowngradeRole extends Notification
{
    use Queueable;
    protected $message;
    protected $role;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($rolesDeleted, $new_role)
    {
        $this->role = $new_role;
        $this->message = count($rolesDeleted) > 1 ? "تم سحب الأدوار التالية منك: " . implode(',', $rolesDeleted) : "تم سحب دور ال" . $rolesDeleted[0] . " منك";
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
            ->subject('أصبوحة 180 || سحب أدوار')
            ->line('تحية طيبة لحضرتك.')
            ->line('')
            ->line('')
            ->line("يؤسفنا إعلامك أنه " . $this->message)
            ->line('أنت الآن ' . $this->role)
            ->line('')
            ->line('كل التوفيق والسداد في خطواتك،')
            ->line('بارك الله وقتك وعملك. 🌸')
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