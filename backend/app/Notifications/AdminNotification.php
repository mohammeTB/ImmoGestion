<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public $inf;
    public $type;
    public function __construct($inf,$type)
    {
        $this->inf = $inf;
        $this->type = $type;
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

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    private function messages(): array
    {
        return [
            'register' => 'Nouveau proprietaire inscrit',
            'reservation' => 'Nouvelle réservation créée',
            'payment' => 'Paiement effectué',
            'message' => 'Nouveau message reçu',
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
