<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MailMemberAdd extends Notification
{
    use Queueable;
    protected $role;
    protected $group;
    protected $url;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($role, $group)
    {
        $this->role = $role;
        $this->group = $group;
        $this->url = 'https://www.platform.osboha180.com' . '/group/group-detail/' . $group->id;
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
            ->subject('أصبوحة 180 || إضافة إلى مجموعة ')
            ->line('أهلًا وسهلًا بك مجددًا')
            ->line('نرجو أن تكون بخير.')
            ->line('')
            ->line('تمت إضافتك ك' . $this->role . ' للمجموعة (' . $this->group->name . ')')
            ->line('')
            ->line('من هنا لطفًا تفضل بالدخول: 👇🏻')
            ->action('رابط المجموعة', $this->url)
            ->line('')
            ->line('كل التوفيق لك. 🌷');
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