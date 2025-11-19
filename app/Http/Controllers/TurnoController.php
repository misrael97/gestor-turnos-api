<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Turno;
use App\Models\User;
use App\Models\Negocio;
use Carbon\Carbon;
use App\Jobs\AlertaTurnoJob;
use App\Jobs\CancelarTurnoJob;
use App\Jobs\AutoCancelarTurnoJob;

class TurnoController extends Controller
{
    // Ver todos los turnos (para admin o super)
    public function index()
    {
        return Turno::with(['usuario', 'negocio'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // Crear un nuevo turno (cliente)
    public function store(Request $r)
    {
        // Validar que venga el negocio_id y opcionalmente hora_deseada
        $r->validate([
            'negocio_id' => 'required|exists:negocios,id',
            'hora_deseada' => 'nullable|date_format:Y-m-d H:i:s'
        ]);

        $userId = $r->user()->id;
        $negocioId = $r->negocio_id;
        $horaDeseada = $r->hora_deseada ?? now();

        // Verificar si el usuario ya tiene un turno activo en esta sucursal
        $turnoExistente = Turno::where('usuario_id', $userId)
            ->where('negocio_id', $negocioId)
            ->whereIn('estado', ['espera', 'llamado'])
            ->first();

        if ($turnoExistente) {
            return response()->json([
                'error' => 'Ya tienes un turno activo en esta sucursal',
                'turno' => $turnoExistente
            ], 422);
        }

        // Si se especifica una hora, verificar que no haya otro turno a esa hora
        if ($r->hora_deseada) {
            $turnoEnHora = Turno::where('negocio_id', $negocioId)
                ->where('hora_inicio', $horaDeseada)
                ->whereIn('estado', ['espera', 'llamado'])
                ->first();

            if ($turnoEnHora) {
                return response()->json([
                    'error' => 'Ya existe un turno reservado para esa hora en esta sucursal',
                    'hora_disponible_siguiente' => Carbon::parse($horaDeseada)->addMinutes(30)->format('Y-m-d H:i:s')
                ], 422);
            }
        }

        $turno = Turno::create([
            'usuario_id' => $userId,
            'negocio_id' => $negocioId,
            'estado' => 'espera',
            'hora_inicio' => $horaDeseada,
        ]);

        // Cargar relaciones
        $turno->load(['usuario', 'negocio']);

        // Programar alertas automáticas (faltan 3 turnos, cancelación por inactividad)
        // dispatch(new AlertaTurnoJob($turno))->delay(now()->addSeconds(10));
        // dispatch(new CancelarTurnoJob($turno))->delay(now()->addSeconds(30));

        return response()->json($turno, 201);
    }

    // Confirmar turno
    public function confirmar($id)
    {
        $turno = Turno::findOrFail($id);
        $turno->update([
            'estado' => 'atendido',
            'hora_fin' => now()
        ]);
        return response()->json(['message' => 'Turno confirmado', 'turno' => $turno]);
    }

    // Cancelar turno (manual o automático)
    public function cancelar($id)
    {
        $turno = Turno::findOrFail($id);
        $turno->update(['estado' => 'cancelado']);
        return response()->json(['message' => 'Turno cancelado', 'turno' => $turno]);
    }

    // Reasignar turno a otro negocio
    public function reasignar(Request $r, $id)
    {
        $turno = Turno::findOrFail($id);
        $turno->update(['negocio_id' => $r->negocio_id]);
        return response()->json(['message' => 'Turno reasignado', 'turno' => $turno]);
    }

    // Llamar siguiente turno (para admin)
    public function llamarSiguiente()
    {
        $turno = Turno::where('estado', 'espera')->orderBy('created_at', 'asc')->first();

        if (!$turno) {
            return response()->json(['message' => 'No hay turnos en espera']);
        }

        $turno->update(['estado' => 'llamado']);

        // Programar auto-cancelación en 30 segundos
        dispatch(new AutoCancelarTurnoJob($turno))->delay(now()->addSeconds(30));

        return response()->json(['message' => 'Turno llamado', 'turno' => $turno]);
    }

    // Ver un turno específico
    public function show($id)
    {
        $turno = Turno::with(['usuario', 'negocio'])->findOrFail($id);
        
        // Calcular posición en la cola
        $posicion = Turno::where('negocio_id', $turno->negocio_id)
            ->where('estado', 'espera')
            ->where('created_at', '<', $turno->created_at)
            ->count();
        
        $turno->posicion_en_cola = $posicion;
        
        return response()->json($turno);
    }

    // Actualizar un turno
    public function update(Request $r, $id)
    {
        $turno = Turno::findOrFail($id);
        $turno->update($r->all());
        $turno->load(['usuario', 'negocio']);
        return response()->json($turno);
    }

    // Eliminar un turno
    public function destroy($id)
    {
        $turno = Turno::findOrFail($id);
        $turno->delete();
        return response()->json(['message' => 'Turno eliminado']);
    }

    // Historial de turnos del usuario autenticado
    public function historial(Request $r)
    {
        // Usar el usuario autenticado en lugar de recibir usuario_id
        $turnos = Turno::where('usuario_id', $r->user()->id)
            ->with(['negocio'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($turnos);
    }

    // Validar QR del turno
    public function validarQR(Request $r)
    {
        $r->validate([
            'turno_id' => 'required|exists:turnos,id'
        ]);

        $turno = Turno::with(['usuario', 'negocio'])->findOrFail($r->turno_id);

        if ($turno->estado === 'cancelado') {
            return response()->json([
                'valido' => false,
                'mensaje' => 'Este turno fue cancelado'
            ], 400);
        }

        if ($turno->estado === 'atendido') {
            return response()->json([
                'valido' => false,
                'mensaje' => 'Este turno ya fue atendido'
            ], 400);
        }

        return response()->json([
            'valido' => true,
            'turno' => $turno,
            'mensaje' => 'Turno válido'
        ]);
    }

    // Generar PDF del ticket
    public function generarPDF($id)
    {
        $turno = Turno::with(['usuario', 'negocio'])->findOrFail($id);
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('turnos.ticket', [
            'turno' => $turno,
            'qr_url' => route('turnos.validar-qr', $turno->id)
        ]);

        return $pdf->download("ticket_turno_{$turno->id}.pdf");
    }
}