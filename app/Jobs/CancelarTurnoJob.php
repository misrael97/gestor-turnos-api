<?php

namespace App\Jobs;

use App\Models\Turno;
use App\Notifications\TurnoCanceladoNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CancelarTurnoJob implements ShouldQueue
{
    use Dispatchable;

    protected $turno;

    public function __construct(Turno $turno)
    {
        $this->turno = $turno;
    }

    public function handle()
    {
        $turno = Turno::find($this->turno->id);
        if ($turno && $turno->estado == 'espera') {
            $turno->update(['estado' => 'cancelado']);
            $turno->usuario->notify(new TurnoCanceladoNotification($turno));
        }
    }
}
