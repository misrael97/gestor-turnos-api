<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NegocioController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\TurnoController;

/*
|--------------------------------------------------------------------------
| Rutas del API Gestor de Turnos
|--------------------------------------------------------------------------
| Estas rutas no usan middleware "web", por lo tanto no requieren token CSRF.
| Son ideales para Postman, Insomnia o tu PWA (Ionic/Angular).
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', fn(Request $r) => $r->user());
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::apiResource('negocios', NegocioController::class);
    Route::apiResource('usuarios', UsuarioController::class);
    Route::apiResource('turnos', TurnoController::class);
    Route::put('/turnos/llamar', [TurnoController::class, 'llamarSiguiente']);
});
