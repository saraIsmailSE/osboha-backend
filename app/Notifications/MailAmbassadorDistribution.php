<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MailAmbassadorDistribution extends Notification
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
            ->subject('أصبوحة 180 || توزيعك لفريق متابعة')
            ->line('أهلًا وسهلًا بك مجددًا')
            ->line('نرجو أن تكون بخير.')
            ->line('')
            ->line('تم توزيعك لفريق كي تبدأ معنا. 😍')
            ->line('')
            ->line('من هنا لطفًا تفضل بالدخول: 👇🏻')
            ->action('رابط المجموعة', $this->url)
            ->line('')
            ->line('سيتم مساعدتك وإرشادك بالخطوات القادمة لذا لا تقلق أبدًا، سنكون بجانبك في كل خطوة. ')
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