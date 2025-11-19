<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TurnoCanceladoNotification extends Notification
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
            'mensaje' => 'Tu turno fue cancelado por inactividad.',
            'turno_id' => $this->turno->id
        ];
    }
}
