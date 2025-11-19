<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Turno;
use App\Notifications\TurnoCanceladoNotification;

class AutoCancelarTurnoJob implements ShouldQueue
{
    use Queueable;

    protected $turno;

    /**
     * Create a new job instance.
     */
    public function __construct(Turno $turno)
    {
        $this->turno = $turno;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Recargar el turno desde la base de datos
        $turno = Turno::find($this->turno->id);

        // Si el turno ya no existe o ya fue confirmado/cancelado, no hacer nada
        if (!$turno || $turno->estado !== 'llamado') {
            return;
        }

        // Auto-cancelar el turno
        $turno->update(['estado' => 'cancelado']);

        // Notificar al usuario
        if ($turno->usuario) {
            $turno->usuario->notify(new TurnoCanceladoNotification($turno));
        }
    }
}
