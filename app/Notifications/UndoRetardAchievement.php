<?php

namespace App\Notifications;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UndoRetardAchievement extends Notification implements ShouldQueue
{
    use Queueable;
    protected $book;
    protected $retardType;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($book, $retard_type)
    {
        $this->book = $book;
        switch ($retard_type) {
            case 'questions':
                $this->retardType = 'أسئلة';
                break;

            case 'general_informations':
                $this->retardType = 'الملخص';
                break;
            case 'thesis':
                $this->retardType = 'أطروحات';
                break;
            default:
                $this->retardType = $retard_type;
        }
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
            ->from('no-reply@osboha180.com', 'Osboha 180')
            ->subject('أصبوحة || تحديث بخصوص طلب توثيقك')
            ->line('تحية طيبة لحضرتك،')
            ->line('نود إعلامك بأنه قد تم إلغاء طلب إعادة التوثيق الخاص بكتاب "' . $this->book . '" والمتعلق بـ "' . $this->retardType . '".')
            ->line('وبالتالي، لم يعد مطلوبًا منك إجراء أي تعديلات إضافية أو تقديم إجابات جديدة.')
            ->line('انتظر شهادتك قريبًا فور انتهائنا من عملية التقييم النهائية.')
            ->line('لك منا خالص التحية والتقدير.');
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
