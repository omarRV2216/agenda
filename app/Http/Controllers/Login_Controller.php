<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\AuthUser;
use App\Models\User;   // 👈 AGREGA ESTA LÍNEA

class Login_Controller extends AppBaseController
{
    public function Login(Request $request)
    {
        // 1. Validar
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ], [
            'username.required' => 'El campo Usuario es requerido',
            'password.required' => 'El campo Password es requerido',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 422);
        }

        // 2. Buscar usuario (AuthUser solo para la consulta SQL)
        $authUser = new AuthUser();
        $userData = $authUser->buscarPorUsername($request->username);

        if (!$userData) {
            return $this->sendError('Usuario o contraseña incorrectos', 401);
        }

        // 3. Verificar contraseña
        if (!Hash::check($request->password, $userData->password)) {
            return $this->sendError('Usuario o contraseña incorrectos', 401);
        }

        // 4. Verificar activo
        if (!$userData->active) {
            return $this->sendError('Usuario inactivo. Contacta al administrador', 403);
        }

        // 5. Generar token con el modelo User (que sí tiene HasApiTokens)
        $user = User::find($userData->id);   // 👈 CAMBIO: User, no AuthUser

        if (!$user) {
            return $this->sendError('Error al generar token', 500);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // 6. Respuesta
        return $this->sendResponse([
            'token'      => $token,
            'token_type' => 'Bearer',
            'user'       => [
                'id'           => $userData->id,
                'name'         => $userData->name,
                'username'     => $userData->username,
                'role'         => $userData->role_name,
                'role_display' => $userData->role_display_name,
                'phone'        => $userData->phone,
                'gender'       => $userData->gender,
            ],
        ], 'Login exitoso');
    }
}