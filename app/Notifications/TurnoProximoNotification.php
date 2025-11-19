<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TurnoProximoNotification extends Notification
{
    use Queueable;

    protected $turno;

    public function __construct($turno)
    {
        $this->turno = $turno;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'mensaje' => 'Tu turno está próximo. Faltan 3 turnos antes del tuyo.',
            'turno_id' => $this->turno->id
        ];
    }
}
