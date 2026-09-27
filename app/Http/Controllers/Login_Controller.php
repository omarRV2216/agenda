<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\AuthUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class Login_Controller extends AppBaseController
{
    public function Login(Request $request)
    {
       
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

        $authUser = new AuthUser();
        $userData = $authUser->buscarPorUsername($request->username);

        if (!$userData) {
            return $this->sendError('Usuario o contraseña incorrectos', 401);
        }

        if (!Hash::check($request->password, $userData->password)) {
            return $this->sendError('Usuario o contraseña incorrectos', 401);
        }

        if (!$userData->active) {
            return $this->sendError('Usuario inactivo. Contacta al administrador', 403);
        }
       
        $user = User::find($userData->id); 

        if (!$user) {
            return $this->sendError('Error al generar token', 500);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

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

     public function Me(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return $this->sendError('No autenticado', 401);
        }

        // Cargar el rol con SQL raw (o con Eloquent si quieres)
        $roleData = DB::table('roles')->where('id', $user->role_id)->first();

        return $this->sendResponse([
            'id'           => $user->id,
            'name'         => $user->name,
            'username'     => $user->username,
            'role'         => $roleData?->name,
            'role_display' => $roleData?->display_name,
            'phone'        => $user->phone,
            'gender'       => $user->gender,
        ], 'Usuario autenticado');
    }

    public function Logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->sendSuccess('Sesión cerrada correctamente');
    }


}