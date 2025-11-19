<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Turno;
use Carbon\Carbon;

class AlertaController extends Controller
{
    /**
     * Obtener alertas del usuario autenticado
     * - Turnos próximos (en espera o llamado)
     * - Turnos que requieren confirmación
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $alertas = [];

        // Obtener turnos en espera o llamados del usuario
        $turnosPendientes = Turno::where('usuario_id', $user->id)
            ->whereIn('estado', ['espera', 'llamado'])
            ->with(['negocio'])
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($turnosPendientes as $turno) {
            // Calcular posición en la fila (solo para turnos en espera)
            if ($turno->estado === 'espera') {
                $posicion = Turno::where('negocio_id', $turno->negocio_id)
                    ->where('estado', 'espera')
                    ->where('created_at', '<', $turno->created_at)
                    ->count() + 1;

                $alertas[] = [
                    'id' => $turno->id,
                    'tipo' => 'turno_proximo',
                    'titulo' => 'Turno en espera',
                    'mensaje' => "Estás en la posición {$posicion} de la fila en {$turno->negocio->nombre}",
                    'turno' => $turno,
                    'posicion' => $posicion,
                    'fecha' => $turno->created_at->toIso8601String(),
                ];
            } else if ($turno->estado === 'llamado') {
                $alertas[] = [
                    'id' => $turno->id,
                    'tipo' => 'turno_llamado',
                    'titulo' => '¡Es tu turno!',
                    'mensaje' => "Tu turno en {$turno->negocio->nombre} está siendo llamado",
                    'turno' => $turno,
                    'fecha' => $turno->updated_at->toIso8601String(),
                ];
            }
        }

        return response()->json($alertas);
    }

    /**
     * Marcar una alerta como leída
     */
    public function marcarLeida($id)
    {
        // Opcional: Implementar tabla de notificaciones si se necesita
        return response()->json(['message' => 'Alerta marcada como leída']);
    }
}
