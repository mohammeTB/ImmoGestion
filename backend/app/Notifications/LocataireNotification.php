<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LocataireNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    protected $type;
    protected $inf;
    protected $msg;
    public function __construct($type, $inf=null, $msg=null)
    {
        $this->type = $type;
        $this->inf = $inf;
        $this->inf = $msg;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    private function messages(): array
    {
        return [
            'accept_reservation' => 'Votre reservation a accepté par le proprietaire',
            'refuse_reservation' => 'Votre reservation a refusé par le proprietaire',
        ];
    }
    public function toArray(object $notifiable): array
    {
        return [
            'message'=>$this->messages()[$this->type].' '.$this->inf??'Notification',
            'type'=>$this->type,
        ];
    }
}
