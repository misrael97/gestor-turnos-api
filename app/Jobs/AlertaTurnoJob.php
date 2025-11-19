<?php

namespace App\Jobs;

use App\Models\Turno;
use App\Notifications\TurnoProximoNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Carbon\Carbon;

class AlertaTurnoJob implements ShouldQueue
{
    use Dispatchable;

    protected $turno;

    public function __construct(Turno $turno)
    {
        $this->turno = $turno;
    }

    public function handle()
    {
        // Recargar turno desde DB
        $turno = Turno::find($this->turno->id);
        
        if (!$turno || $turno->estado !== 'espera') {
            return;
        }

        // Calcular cuántos turnos faltan
        $faltan = Turno::where('negocio_id', $turno->negocio_id)
            ->where('estado', 'espera')
            ->where('created_at', '<', $turno->created_at)
            ->count();

        // Notificar si faltan 3 turnos o menos
        if ($faltan <= 3 && $faltan >= 0) {
            $turno->usuario->notify(new TurnoProximoNotification($turno));
        }

        // Alerta por tiempo de espera excesivo (más de 30 minutos)
        $tiempoEspera = Carbon::parse($turno->created_at)->diffInMinutes(now());
        if ($tiempoEspera > 30) {
            // Aquí podrías enviar una notificación especial al admin
            \Log::warning("Turno #{$turno->id} lleva {$tiempoEspera} minutos esperando");
        }
    }
}
