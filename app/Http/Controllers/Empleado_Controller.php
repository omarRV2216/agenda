<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;   
use Illuminate\Support\Facades\Log; 
use App\Models\Empleado;

class Empleado_Controller extends AppBaseController{
    
    public function Create (Request $request){
        $validator = Validator::make($request->all(), [
            'role_id'       => 'required|integer|exists:roles,id',
            'name'          => 'required|string|min:3|max:255',
            'username'      => 'required|string|min:3|max:255|unique:users,username',
            'password'      => 'required|string|min:6|max:255',
            'phone'         => 'required|string|min:10|max:10',
            'gender' => 'required|string|in:M,F,O',
            'active'        => 'required|boolean',
        ], [
            // role_id
            'role_id.required' => 'El campo Rol es requerido',
            'role_id.integer'  => 'El campo Rol debe ser un número entero',
            'role_id.exists'   => 'El Rol seleccionado no existe',

            // name
            'name.required'    => 'El campo Nombre es requerido',
            'name.string'      => 'El campo Nombre debe ser texto',
            'name.min'         => 'El campo Nombre debe tener al menos 3 caracteres',
            'name.max'         => 'El campo Nombre no debe exceder 255 caracteres',

            // username
            'username.required' => 'El campo Usuario es requerido',
            'username.string'   => 'El campo Usuario debe ser texto',
            'username.min'      => 'El campo Usuario debe tener al menos 3 caracteres',
            'username.max'      => 'El campo Usuario no debe exceder 255 caracteres',
            'username.unique'   => 'Ese nombre de usuario ya está registrado',

            // password
            'password.required' => 'El campo Contraseña es requerido',
            'password.string'   => 'El campo Contraseña debe ser texto',
            'password.min'      => 'El campo Contraseña debe tener al menos 6 caracteres',
            'password.max'      => 'El campo Contraseña no debe exceder 255 caracteres',

            // phone
            'phone.required'    => 'El campo Teléfono es requerido',
            'phone.string'      => 'El campo Teléfono debe ser texto',
            'phone.min'         => 'El campo Teléfono debe tener exactamente 10 caracteres',
            'phone.max'         => 'El campo Teléfono debe tener exactamente 10 caracteres',

            // gender
            'gender.required'   => 'El campo Sexo es requerido',
            'gender.string'     => 'El campo Sexo debe ser texto',
            'gender.in'         => 'El campo Sexo debe ser M, F u O',

            // active
            'active.required'   => 'El campo Activo es requerido',
            'active.boolean'    => 'El campo Activo debe ser 0 o 1',
        ]);

        if ($validator->fails()) {
        return $this->sendError($validator->errors()->first());
        }

        $validated = $validator->validated();

        $empleado = new Empleado();
        
        foreach ($validated as $key => $value) {
            $empleado->$key = $value;
        }

        try {
            $idEmpleado = $empleado->Create_user();

            if ($idEmpleado === false) {
                return $this->sendError('Error al crear el empleado');
            }

            return $this->sendSuccess("Empleado creado exitosamente");

        } catch (\Exception $e) {
            return $this->sendError('Error: ' . $e->getMessage());
        }

    }
}
