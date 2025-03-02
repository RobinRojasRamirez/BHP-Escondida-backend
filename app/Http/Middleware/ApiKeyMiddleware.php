<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Obtener la API_KEY desde el encabezado de la solicitud
        $apiKey = $request->header('X-API-KEY');
        
        // Verifica que la API key sea correcta
        if ($apiKey !== env('API_KEY')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
    
        return $next($request);
    }
}