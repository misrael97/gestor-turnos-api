<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Notifications\TwoFactorCodeNotification;
use Carbon\Carbon;


class AuthController extends Controller
{
    public function register(Request $r)
    {
        $validator = Validator::make($r->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role_id' => 'nullable|integer|exists:roles,id|in:1,2,3',
            'sucursal_id' => 'nullable|integer|exists:negocios,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Si no envías role_id, por defecto es Cliente (3)
        $user = User::create([
            'name' => $r->name,
            'email' => $r->email,
            'password' => bcrypt($r->password),
            'role_id' => $r->role_id ?? 3,
            'sucursal_id' => $r->sucursal_id ?? null
        ]);

        // Cargar la relación del role
        $user->load('role', 'negocio');

        $token = $user->createToken('api-token')->plainTextToken;
        return response()->json(['token' => $token, 'user' => $user]);
    }

    public function login(Request $r)
    {
        // Debug: ver TODOS los datos recibidos
        \Log::info('=== LOGIN ATTEMPT ===');
        \Log::info('Request data:', $r->all());
        \Log::info('Email: ' . ($r->email ?? 'NULL'));
        \Log::info('Password exists: ' . ($r->has('password') ? 'YES' : 'NO'));
        \Log::info('IP: ' . $r->ip());
        
        if (!Auth::attempt($r->only('email', 'password'))) {
            \Log::warning('AUTH FAILED for email: ' . ($r->email ?? 'NULL'));
            return response()->json(['error' => 'Credenciales inválidas'], 401);
        }

        \Log::info('AUTH SUCCESS for: ' . $r->email);
        $user = Auth::user();
        
        // Generar código de 2FA de 6 dígitos
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        \Log::info('Generated 2FA code: ' . $code . ' for user: ' . $user->email);
        
        // Guardar código con expiración de 10 minutos
        $user->two_factor_code = $code;
        $user->two_factor_expires_at = Carbon::now()->addMinutes(10);
        $user->two_factor_verified = false;
        $user->save();
        
        // Enviar código por email
        $user->notify(new TwoFactorCodeNotification($code));
        
        $response = [
            'requires_2fa' => true,
            'message' => 'Código de verificación enviado a tu email',
            'email' => $user->email
        ];
        
        \Log::info('Sending 2FA response:', $response);
        
        return response()->json($response);
    }

    public function logout(Request $r)
    {
        $r->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Sesión cerrada']);
    }

    public function verify2FA(Request $r)
    {
        $validator = Validator::make($r->all(), [
            'email' => 'required|email',
            'code' => 'required|string|size:6'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $r->email)->first();

        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        // Verificar que el código coincida
        if ($user->two_factor_code !== $r->code) {
            return response()->json(['error' => 'Código inválido'], 401);
        }

        // Verificar que el código no haya expirado
        if (Carbon::now()->isAfter($user->two_factor_expires_at)) {
            return response()->json(['error' => 'Código expirado'], 401);
        }

        // Marcar como verificado y limpiar código
        $user->two_factor_verified = true;
        $user->two_factor_code = null;
        $user->two_factor_expires_at = null;
        $user->save();

        // Cargar relaciones
        $user->load('role', 'negocio');

        // Generar token
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user
        ]);
    }

    public function resend2FA(Request $r)
    {
        $validator = Validator::make($r->all(), [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $r->email)->first();

        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        // Generar nuevo código de 6 dígitos
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Actualizar código con nueva expiración
        $user->two_factor_code = $code;
        $user->two_factor_expires_at = Carbon::now()->addMinutes(10);
        $user->save();

        // Reenviar código por email
        $user->notify(new TwoFactorCodeNotification($code));

        return response()->json([
            'message' => 'Código reenviado exitosamente a tu email'
        ]);
    }

    public function me(Request $r)
    {
        $user = $r->user();
        $user->load('role', 'negocio');
        return response()->json($user);
    }

    // Método para crear usuarios Admin/Agente (solo accesible para Admins)
    public function createUser(Request $r)
    {
        // Verificar que el usuario autenticado sea Administrador (role_id = 1)
        if ($r->user()->role_id !== 1) {
            return response()->json(['error' => 'No tienes permisos para crear usuarios'], 403);
        }

        $validator = Validator::make($r->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role_id' => 'required|exists:roles,id|in:1,2,3', // 1=Admin, 2=Agente, 3=Cliente
            'sucursal_id' => 'nullable|integer|exists:negocios,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $r->name,
            'email' => $r->email,
            'password' => bcrypt($r->password),
            'role_id' => $r->role_id,
            'sucursal_id' => $r->sucursal_id
        ]);

        $user->load('role', 'negocio');

        return response()->json([
            'message' => 'Usuario creado exitosamente',
            'user' => $user
        ], 201);
    }
}

