<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue; // 1. Ya lo tienes aquí, bien.
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// 2. EL CAMBIO CLAVE ESTÁ AQUÍ ABAJO (agregué 'implements ShouldQueue')
class TwoFactorCodeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $code;

    /**
     * Create a new notification instance.
     */
    public function __construct($code)
    {
        $this->code = $code;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // He mantenido tu lógica original con la vista personalizada
        return (new MailMessage)
            ->subject('Código de Verificación 2FA - Gestor de Turnos')
            ->view('emails.two-factor-code', [
                'codigo' => $this->code,
                'nombre' => $notifiable->name
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'code' => $this->code
        ];
    }
}