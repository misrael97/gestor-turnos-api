<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\NegocioController;
use App\Http\Controllers\TurnoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\AlertaController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\NotificationController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/login/verify-2fa', [AuthController::class, 'verify2FA']);
Route::post('/login/resend-2fa', [AuthController::class, 'resend2FA']);
Route::post('/register', [AuthController::class, 'register']);

// 📺 RUTA PÚBLICA PARA DISPLAY DE TURNOS (sin autenticación)
Route::get('/display/{sucursal_id}', [TurnoController::class, 'displayPublico']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Ruta protegida para crear usuarios Admin/Agente (solo para administradores)
    Route::post('/users/create', [AuthController::class, 'createUser']);
    
    // Gestión de usuarios (Admin)
    Route::apiResource('usuarios', UsuarioController::class);

    Route::apiResource('negocios', NegocioController::class);
    
    // Alias para sucursales (mismo que negocios)
    Route::get('/sucursales', [NegocioController::class, 'index']);
    Route::get('/sucursales/{id}', [NegocioController::class, 'show']);
    
    // 🔔 RUTAS DE NOTIFICACIONES PUSH (FCM)
    Route::post('/fcm-tokens', [NotificationController::class, 'storeToken']);
    Route::delete('/fcm-tokens/{token}', [NotificationController::class, 'deleteToken']);
    Route::post('/notifications/user/{userId}', [NotificationController::class, 'sendToUser']);
    Route::post('/notifications/multiple', [NotificationController::class, 'sendToMultipleUsers']);
    Route::get('/notifications/tokens/{userId}', [NotificationController::class, 'getUserTokens']);
    
    // Rutas específicas de turnos (ANTES de apiResource)
    Route::get('/turnos/historial', [TurnoController::class, 'historial']);
    Route::post('/turnos/llamar-siguiente', [TurnoController::class, 'llamarSiguiente']);
    Route::post('/turnos/validar-qr', [TurnoController::class, 'validarQR']);
    Route::get('/turnos/{id}/pdf', [TurnoController::class, 'generarPDF']);
    Route::put('/turnos/{id}/confirmar', [TurnoController::class, 'confirmar']);
    Route::put('/turnos/{id}/cancelar', [TurnoController::class, 'cancelar']);
    Route::put('/turnos/{id}/reasignar', [TurnoController::class, 'reasignar']);
    Route::put('/turnos/{id}/reasignar-cola', [TurnoController::class, 'reasignarCola']);
    
    // Rutas CRUD genéricas de turnos
    Route::apiResource('turnos', TurnoController::class);

    // Alertas del usuario
    Route::get('/alertas', [AlertaController::class, 'index']);
    Route::put('/alertas/{id}/leida', [AlertaController::class, 'marcarLeida']);

    Route::get('/reportes/dia', [ReporteController::class, 'turnosPorDia']);
    Route::get('/reportes/espera', [ReporteController::class, 'tiempoPromedioEspera']);
    Route::get('/reportes/productividad', [ReporteController::class, 'productividadAgentes']);
    Route::get('/reportes/pdf/{tipo}', [ReporteController::class, 'exportarPDF']);
    Route::get('/reportes-globales', [ReporteController::class, 'reportesGlobales']);

    // Exportar reporte PDF
    // $pdf = Pdf::loadView('reportes.plantilla', ['titulo' => $titulo, 'datos' => $datos]);
    // return $pdf->download("reporte_{$tipo}_" . now()->format('Ymd_His') . '.pdf');



});