<?php

namespace App\Http\Controllers;

use App\Models\FcmToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    /**
     * Guardar o actualizar token FCM del usuario
     */
    public function storeToken(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'token' => 'required|string|max:500',
            'device_info' => 'nullable|string',
            'platform' => 'required|in:web,android,ios'
        ]);

        // Desactivar tokens antiguos del mismo dispositivo
        FcmToken::where('user_id', $validated['user_id'])
            ->where('token', '!=', $validated['token'])
            ->update(['active' => false]);

        // Crear o actualizar el token
        $fcmToken = FcmToken::updateOrCreate(
            [
                'user_id' => $validated['user_id'],
                'token' => $validated['token']
            ],
            [
                'device_info' => $validated['device_info'],
                'platform' => $validated['platform'],
                'active' => true,
                'last_used_at' => now()
            ]
        );

        return response()->json([
            'message' => 'Token registrado exitosamente',
            'data' => $fcmToken
        ]);
    }

    /**
     * Eliminar token FCM
     */
    public function deleteToken($token)
    {
        FcmToken::where('token', $token)->delete();

        return response()->json([
            'message' => 'Token eliminado exitosamente'
        ]);
    }

    /**
     * Enviar notificación push a un usuario específico
     */
    public function sendToUser(Request $request, $userId)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'body' => 'required|string',
            'data' => 'nullable|array'
        ]);

        $user = User::findOrFail($userId);
        $tokens = FcmToken::active()->forUser($userId)->pluck('token')->toArray();

        if (empty($tokens)) {
            return response()->json([
                'message' => 'No hay tokens activos para este usuario'
            ], 404);
        }

        $result = $this->sendFcmNotification($tokens, $validated['title'], $validated['body'], $validated['data'] ?? []);

        return response()->json([
            'message' => 'Notificación enviada',
            'result' => $result
        ]);
    }

    /**
     * Enviar notificación a múltiples usuarios
     */
    public function sendToMultipleUsers(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'title' => 'required|string',
            'body' => 'required|string',
            'data' => 'nullable|array'
        ]);

        $tokens = FcmToken::active()
            ->whereIn('user_id', $validated['user_ids'])
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return response()->json([
                'message' => 'No hay tokens activos para estos usuarios'
            ], 404);
        }

        $result = $this->sendFcmNotification($tokens, $validated['title'], $validated['body'], $validated['data'] ?? []);

        return response()->json([
            'message' => 'Notificaciones enviadas',
            'result' => $result
        ]);
    }

    /**
     * Enviar notificación mediante FCM HTTP v1 API
     */
    private function sendFcmNotification(array $tokens, string $title, string $body, array $data = [])
    {
        $projectId = env('FIREBASE_PROJECT_ID');
        $credentialsPath = base_path(env('FIREBASE_CREDENTIALS'));

        // Logging detallado para diagnóstico
        Log::info('🔍 Verificando configuración de Firebase');
        Log::info('Project ID: ' . ($projectId ?? 'NULL'));
        Log::info('Credentials Path: ' . $credentialsPath);
        Log::info('File exists: ' . (file_exists($credentialsPath) ? 'YES' : 'NO'));

        if (!$projectId || !file_exists($credentialsPath)) {
            Log::error('❌ Firebase no configurado correctamente');
            Log::error('Project ID presente: ' . ($projectId ? 'SI' : 'NO'));
            Log::error('Archivo existe: ' . (file_exists($credentialsPath) ? 'SI' : 'NO'));
            return ['error' => 'FCM no configurado'];
        }

        Log::info('✅ Configuración de Firebase OK, obteniendo access token...');
        $accessToken = $this->getAccessToken($credentialsPath);

        if (!$accessToken) {
            Log::error('❌ No se pudo obtener access token de Firebase');
            return ['error' => 'Error de autenticación con Firebase'];
        }

        Log::info('✅ Access token obtenido correctamente');

        $results = [];

        // Enviar a cada token usando FCM HTTP v1 API
        foreach ($tokens as $token) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json'
                ])->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $title,
                            'body' => $body
                        ],
                        'data' => array_map('strval', $data), // FCM v1 requiere strings
                        'webpush' => [
                            'notification' => [
                                'icon' => '/assets/icon/favicon.png',
                                'badge' => '/assets/icon/favicon.png'
                            ],
                            'fcm_options' => [
                                'link' => url('/')
                            ]
                        ]
                    ]
                ]);

                $responseData = $response->json();
                
                // Si el token es inválido, marcarlo como inactivo
                if ($response->failed() && isset($responseData['error'])) {
                    $errorCode = $responseData['error']['status'] ?? '';
                    if (in_array($errorCode, ['INVALID_ARGUMENT', 'NOT_FOUND', 'UNREGISTERED'])) {
                        FcmToken::where('token', $token)->update(['active' => false]);
                    }
                }

                $results[] = [
                    'token' => substr($token, 0, 20) . '...',
                    'success' => $response->successful(),
                    'response' => $responseData
                ];

            } catch (\Exception $e) {
                Log::error('Error enviando notificación FCM v1: ' . $e->getMessage());
                $results[] = [
                    'token' => substr($token, 0, 20) . '...',
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Obtener Access Token usando Service Account
     */
    private function getAccessToken(string $credentialsPath)
    {
        try {
            putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $credentialsPath);
            
            $client = new \Google\Client();
            $client->useApplicationDefaultCredentials();
            $client->setScopes(['https://www.googleapis.com/auth/firebase.messaging']);
            
            $token = $client->fetchAccessTokenWithAssertion();
            
            return $token['access_token'] ?? null;
        } catch (\Exception $e) {
            Log::error('Error obteniendo access token de Google: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener todos los tokens de un usuario
     */
    public function getUserTokens($userId)
    {
        $tokens = FcmToken::active()->forUser($userId)->get();

        return response()->json([
            'data' => $tokens
        ]);
    }

    /**
     * 🧪 Probar notificación push al usuario autenticado
     */
    public function testNotification(Request $request)
    {
        $user = $request->user();
        
        Log::info('🧪 Probando notificación para usuario: ' . $user->id);
        
        $tokens = FcmToken::active()->where('user_id', $user->id)->pluck('token')->toArray();
        
        Log::info('📱 Tokens encontrados: ' . count($tokens));
        
        if (empty($tokens)) {
            return response()->json([
                'error' => 'No hay tokens FCM registrados para tu usuario',
                'user_id' => $user->id,
                'message' => 'Asegúrate de que la PWA haya solicitado permisos de notificación'
            ], 404);
        }

        $result = $this->sendFcmNotification(
            $tokens,
            '🧪 Prueba de Notificación',
            'Si ves esto, las notificaciones funcionan correctamente! ✅',
            ['type' => 'test', 'timestamp' => now()->toString()]
        );

        return response()->json([
            'message' => 'Notificación de prueba enviada',
            'user' => $user->name,
            'tokens_count' => count($tokens),
            'results' => $result
        ]);
    }
}
