<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\RegisterRequest;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
/**
 * Controlador de Autenticación.
 *
 * Proporciona funcionalidades de registro, inicio de sesión, 
 * cierre de sesión y actualización de tokens para usuarios autenticados con JWT.
 *
 * @category Controlador
 * @package  App\Http\Controllers
 * @author   Tu Nombre
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://tu-api-documentation.com
 */
class AuthController extends Controller
{
    /**
     * Constructor del controlador.
     *
     * Aplica el middleware para proteger las rutas, excepto `login` y `register`.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register', 'verifyCode']]);
    }

    public function register(Request $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Usuario registrado correctamente',
            'data' => $user
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        $user = User::where('email', $credentials['email'])->first();
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Credenciales incorrectas',
                'data' => null
            ], 401);
        }
        // Generar código de 6 dígitos numéricos
        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        // Guardar en caché por 5 minutos
        Cache::put("verification_code_{$user->email}", $code, now()->addMinutes(5));
        // Enviar por correo (usa una mailable propia)
        Mail::to($user->email)->send(new \App\Mail\VerificationCodeMail($code));
        return response()->json([
            'status' => 'success',
            'requiere_verificacion' => true,
            'message' => 'Código de verificación enviado al correo',
            'data' => [
                'email' => $user->email,
                "code" => $code,
            ]
        ]);
    }

    public function verifyCode(Request $request)
    {
        $email = $request->input('email');
        $enteredCode = $request->input('code');
        $storedCode = Cache::get("verification_code_{$email}");
        if ($storedCode && $enteredCode === $storedCode) {
            $user = User::where('email', $email)->first();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Usuario no encontrado',
                    'data' => null
                ], 404);
            }
            $token = JWTAuth::fromUser($user);
            Cache::forget("verification_code_{$email}");
            return response()->json([
                'status' => 'success',
                'message' => 'Código verificado correctamente',
                'data' => [
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => JWTAuth::factory()->getTTL() * 60
                ]
            ], 200);
        }
        return response()->json([
            'status' => 'error',
            'message' => 'Código de verificación incorrecto o expirado',
            'data' => null
        ], 400);
    }
    
    public function me()
    {
          $user = auth()->user();
          $user['token'] = auth()->getToken()->get();
          return ResponseHelper::formatResponse('Usuario autenticado', 200, false, auth()->user());
    }

    public function logout()
    {
        try {
            auth()->logout();
            return ResponseHelper::formatResponse('Sesión cerrada exitosamente', 200, false);
        } catch (JWTException $e) {
            return ResponseHelper::formatResponse('No se pudo cerrar la sesión', 500, true);
        }
    }
    
    public function refresh()
    {
         return ResponseHelper::formatResponse('Token refrescado', 200, false, [
         'access_token' => auth()->refresh(),
         'token_type' => 'bearer',
         'expires_in' => auth()->factory()->getTTL() * 60
         ]);
    }
}