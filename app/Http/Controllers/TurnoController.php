<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Turno;
use App\Models\User;
use App\Models\Negocio;
use App\Models\FcmToken;
use Carbon\Carbon;
use App\Jobs\AlertaTurnoJob;
use App\Jobs\CancelarTurnoJob;
use App\Jobs\AutoCancelarTurnoJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
        // Validar que venga el negocio_id y opcionalmente campos de programación
        $r->validate([
            'negocio_id' => 'required|exists:negocios,id',
            'hora_deseada' => 'nullable|date_format:Y-m-d H:i:s',
            'tipo' => 'nullable|in:presencial,online',
            'programado' => 'nullable|boolean',
            'fecha_programada' => 'nullable|date',
            'hora_programada' => 'nullable|date_format:H:i'
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

        // Preparar datos del turno
        $turnoData = [
            'usuario_id' => $userId,
            'negocio_id' => $negocioId,
            'estado' => 'espera',
            'hora_inicio' => $horaDeseada,
            'tipo' => $r->tipo ?? 'presencial',
            'programado' => $r->programado ?? false,
        ];

        // Agregar campos de programación si existen
        if ($r->programado && $r->fecha_programada && $r->hora_programada) {
            $turnoData['fecha_programada'] = $r->fecha_programada;
            $turnoData['hora_programada'] = $r->hora_programada;
        }

        $turno = Turno::create($turnoData);

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
        try {
            Log::info('🔔 Iniciando llamarSiguiente');
            
            $turno = Turno::where('estado', 'espera')->orderBy('created_at', 'asc')->first();
            Log::info('✅ Turno encontrado: ' . ($turno ? $turno->id : 'ninguno'));

            if (!$turno) {
                return response()->json(['message' => 'No hay turnos en espera']);
            }

            Log::info('📝 Actualizando estado del turno a "llamado"');
            $turno->update(['estado' => 'llamado']);
            Log::info('✅ Estado actualizado correctamente');

            // ENVIAR NOTIFICACIÓN PUSH AL USUARIO (con manejo de errores)
            try {
                Log::info('📧 Enviando notificación de turno llamado');
                $this->enviarNotificacionTurnoLlamado($turno);
                Log::info('✅ Notificación enviada');
            } catch (\Exception $e) {
                Log::error('❌ Error enviando notificación de turno llamado: ' . $e->getMessage());
            }

            // NOTIFICAR A LOS SIGUIENTES 3 EN LA COLA (con manejo de errores)
            try {
                Log::info('📧 Notificando siguientes 3 en cola');
                $this->notificarSiguientesTresEnCola($turno->negocio_id);
                Log::info('✅ Notificaciones de cola enviadas');
            } catch (\Exception $e) {
                Log::error('❌ Error notificando siguientes en cola: ' . $e->getMessage());
            }

            // Programar auto-cancelación en 30 segundos (con manejo de errores)
            try {
                Log::info('⏰ Programando auto-cancelación');
                dispatch(new AutoCancelarTurnoJob($turno))->delay(now()->addSeconds(30));
                Log::info('✅ Auto-cancelación programada');
            } catch (\Exception $e) {
                Log::error('❌ Error programando auto-cancelación: ' . $e->getMessage());
            }

            Log::info('🎉 llamarSiguiente completado exitosamente');
            return response()->json(['message' => 'Turno llamado', 'turno' => $turno]);
            
        } catch (\Exception $e) {
            Log::error('💥 ERROR CRÍTICO en llamarSiguiente: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'error' => 'Error al llamar siguiente turno',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Notificar a los siguientes 3 turnos en espera
     */
    private function notificarSiguientesTresEnCola($negocioId)
    {
        $turnosEnEspera = Turno::where('negocio_id', $negocioId)
            ->where('estado', 'espera')
            ->orderBy('created_at', 'asc')
            ->take(3)
            ->get();

        foreach ($turnosEnEspera as $index => $turno) {
            $posicion = $index + 1;
            $this->enviarNotificacionPosicionEnCola($turno, $posicion);
        }
    }

    /**
     * Enviar notificación de posición en cola
     */
    private function enviarNotificacionPosicionEnCola($turno, $posicion)
    {
        $tokens = FcmToken::active()
            ->where('user_id', $turno->usuario_id)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return;
        }

        $projectId = env('FIREBASE_PROJECT_ID');
        $credentialsPath = base_path(env('FIREBASE_CREDENTIALS'));

        if (!$projectId || !file_exists($credentialsPath)) {
            return;
        }

        $accessToken = $this->getFirebaseAccessToken($credentialsPath);
        if (!$accessToken) {
            return;
        }

        $negocio = $turno->negocio ?? Negocio::find($turno->negocio_id);
        $mensaje = $posicion === 1 
            ? "¡Eres el siguiente! Prepárate para ser atendido en {$negocio->nombre}"
            : "Faltan {$posicion} personas delante de ti en {$negocio->nombre}";

        foreach ($tokens as $token) {
            try {
                Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json'
                ])->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $posicion === 1 ? '¡Tu turno es el siguiente!' : "Faltan {$posicion} personas",
                            'body' => $mensaje
                        ],
                        'data' => [
                            'turnoId' => (string)$turno->id,
                            'type' => 'posicion_cola',
                            'posicion' => (string)$posicion,
                            'sucursal' => $negocio->nombre
                        ],
                        'webpush' => [
                            'notification' => [
                                'icon' => '/assets/icon/favicon.png',
                                'badge' => '/assets/icon/favicon.png'
                            ]
                        ]
                    ]
                ]);
            } catch (\Exception $e) {
                Log::error('Error enviando notificación de posición en cola: ' . $e->getMessage());
            }
        }
    }

    /**
     * Enviar notificación push cuando se llama un turno
     */
    private function enviarNotificacionTurnoLlamado($turno)
    {
        $tokens = FcmToken::active()
            ->where('user_id', $turno->usuario_id)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return;
        }

        $projectId = env('FIREBASE_PROJECT_ID');
        $credentialsPath = base_path(env('FIREBASE_CREDENTIALS'));

        if (!$projectId || !file_exists($credentialsPath)) {
            return;
        }

        $accessToken = $this->getFirebaseAccessToken($credentialsPath);
        if (!$accessToken) {
            return;
        }

        $negocio = $turno->negocio ?? Negocio::find($turno->negocio_id);

        $data = [
            'turnoId' => (string)$turno->id,
            'type' => 'turno_llamado',
            'sucursal' => $negocio->nombre
        ];

        foreach ($tokens as $token) {
            try {
                Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json'
                ])->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => '¡Tu turno ha sido llamado!',
                            'body' => "Por favor acércate a {$negocio->nombre}. Turno #{$turno->id}"
                        ],
                        'data' => $data,
                        'webpush' => [
                            'notification' => [
                                'icon' => '/assets/icon/favicon.png',
                                'badge' => '/assets/icon/favicon.png',
                                'requireInteraction' => true
                            ]
                        ]
                    ]
                ]);
            } catch (\Exception $e) {
                Log::error('Error enviando notificación FCM v1: ' . $e->getMessage());
            }
        }
    }

    /**
     * Obtener Access Token de Firebase
     */
    private function getFirebaseAccessToken(string $credentialsPath)
    {
        try {
            putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $credentialsPath);
            
            $client = new \Google\Client();
            $client->useApplicationDefaultCredentials();
            $client->setScopes(['https://www.googleapis.com/auth/firebase.messaging']);
            
            $token = $client->fetchAccessTokenWithAssertion();
            
            return $token['access_token'] ?? null;
        } catch (\Exception $e) {
            Log::error('Error obteniendo access token: ' . $e->getMessage());
            return null;
        }
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

    /**
     * 📺 DISPLAY PÚBLICO - Sin autenticación
     * Muestra turnos activos de una sucursal específica
     */
    public function displayPublico($sucursal_id)
    {
        $turnos = Turno::with(['usuario', 'negocio'])
            ->where('negocio_id', $sucursal_id)
            ->whereIn('estado', ['espera', 'llamado'])
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json($turnos);
    }

    /**
     * 🔄 Reasignar turno a una cola específica
     */
    public function reasignarCola(Request $r, $id)
    {
        $r->validate([
            'cola' => 'required|string|max:100'
        ]);

        $turno = Turno::findOrFail($id);
        $turno->update(['cola' => $r->cola]);
        $turno->load(['usuario', 'negocio']);

        return response()->json([
            'message' => "Turno reasignado a cola: {$r->cola}",
            'turno' => $turno
        ]);
    }
}