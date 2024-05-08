<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MailAmbassadorDistributionToYourTeam extends Notification
{
    use Queueable;
    protected $groupId;
    protected $url;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($groupId)
    {
        $this->groupId = $groupId;
        $this->url = 'https://www.platform.osboha180.com' . '/group/group-detail/' . $this->groupId;
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
            ->subject('أصبوحة 180 ||اكتمل طلبك')
            ->line('السلام عليكم ورحمة الله وبركاته')
            ->line('نرجو أن تكون بخير قائدنا الكريم.🌸')
            ->line('')
            ->line('لقد تم توزيع المشتركين الجدد لفريقك حسب طلبك، فاحرص على التواصل معهم مباشرة، واستقبالهم بشكل مثالي.')
            ->line('')
            ->line('لُطفًا تفضل بالدخول من هُنا👇🏻')
            ->action('رابط المجموعة', $this->url)
            ->line('')
            ->line('قوى الله عزائمكم، وبورك جهدكم.🌸');
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
