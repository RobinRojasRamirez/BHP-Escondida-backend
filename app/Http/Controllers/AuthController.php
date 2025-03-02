<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\RegisterRequest;
use Tymon\JWTAuth\Exceptions\JWTException;
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
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    public function register(RegisterRequest $request)
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

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Credenciales incorrectas',
                'data' => null
            ], 401);
        }

        return ResponseHelper::formatResponse('Inicio de sesión exitoso', 200, false, [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
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
