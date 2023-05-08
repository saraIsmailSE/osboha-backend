<?php

namespace App\Notifications;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
//custom
use Illuminate\Support\Facades\Lang;
use Illuminate\Auth\Notifications\ResetPassword;

class MailResetPasswordNotification extends Notification
{
    use Queueable;
    protected $pageUrl;
    public $token;
    public $timer;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($token)
    {
        $this->token = $token;
        $this->pageUrl = 'https://www.platform.osboha180.com' . '/auth/reset-password/';
        $this->timer = config('auth.passwords.users.expire');
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
            ->subject('أصبوحة || تغيير كلمة المرور')
            ->line('من المهم أن تحفظ كلمة المرور الخاصة بك، لأنها سبيل الوصول إلى وثائقك.')
            ->line('لقد استلمنا طلب تغيير كلمة السر على الموقع.')
            ->action('اضغط هنا لتغير كلمة السر', $this->pageUrl . $this->token)
            ->line('ستنتهي صلاحية رابط إعادة تعيين كلمة المرور بعد', $this->timer)
            ->line('إذا لم تطلب إعادة تعيين كلمة المرور ، فلا يلزم اتخاذ أي إجراء آخر')
            ->line('همسة، حاول الاحتفاظ بكلمة السر الجديدة في مكان يسهل عليك الوصول اليه');
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