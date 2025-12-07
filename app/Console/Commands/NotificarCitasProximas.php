<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Turno;
use App\Models\FcmToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificarCitasProximas extends Command
{
    protected $signature = 'turnos:notificar-citas';
    protected $description = 'Enviar notificaciones de citas programadas próximas (1 minuto antes)';

    public function handle()
    {
        $ahora = Carbon::now();
        $enUnMinuto = $ahora->copy()->addMinute();

        // Buscar citas programadas que estén entre ahora y 1 minuto adelante
        $citasProximas = Turno::where('programado', true)
            ->where('estado', 'espera')
            ->whereNotNull('fecha_programada')
            ->whereNotNull('hora_programada')
            ->get()
            ->filter(function($turno) use ($ahora, $enUnMinuto) {
                $fechaHoraCita = Carbon::parse($turno->fecha_programada . ' ' . $turno->hora_programada);
                return $fechaHoraCita->between($ahora, $enUnMinuto);
            });

        foreach ($citasProximas as $turno) {
            $this->enviarNotificacionCitaProxima($turno);
        }

        $this->info("Procesadas {$citasProximas->count()} citas próximas");
        return 0;
    }

    private function enviarNotificacionCitaProxima($turno)
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

        $negocio = $turno->negocio;
        $fechaHora = Carbon::parse($turno->fecha_programada . ' ' . $turno->hora_programada)
            ->format('d/m/Y H:i');

        foreach ($tokens as $token) {
            try {
                Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json'
                ])->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => '¡Tu cita es en 1 minuto!',
                            'body' => "Recuerda tu cita en {$negocio->nombre} a las {$fechaHora}"
                        ],
                        'data' => [
                            'turnoId' => (string)$turno->id,
                            'type' => 'recordatorio_cita',
                            'sucursal' => $negocio->nombre
                        ],
                        'webpush' => [
                            'notification' => [
                                'icon' => '/assets/icon/favicon.png',
                                'badge' => '/assets/icon/favicon.png',
                                'requireInteraction' => true,
                                'tag' => 'cita-' . $turno->id
                            ]
                        ]
                    ]
                ]);
            } catch (\Exception $e) {
                Log::error('Error enviando notificación de cita próxima: ' . $e->getMessage());
            }
        }
    }

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
}
