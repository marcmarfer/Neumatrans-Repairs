<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json(['message' => 'Credenciales inválidas'], 401);
        }

        $user = auth('api')->user();
        if (! $user->is_integration) {
            return response()->json(['message' => 'Acceso no permitido'], 403);
        }

        return response()->json([
            'token' => $token,
            'user'  => $user,
        ]);
    }
} 