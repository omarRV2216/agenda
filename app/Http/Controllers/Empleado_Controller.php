<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;   
use Illuminate\Support\Facades\Log; 
use App\Models\Empleado;

class Empleado_Controller extends AppBaseController{

    public function Get(Request $req){
        $rules = [
            'id'       => 'nullable|integer|min:1',
            'name'     => 'nullable|string|min:1|max:255',
            'username' => 'nullable|string|min:1|max:255',
            'phone'    => 'nullable|string|min:1|max:20',
            'gender'   => 'nullable|string|in:M,F,O',
            'active'   => 'nullable|boolean',
            'search'   => 'nullable|string|min:1|max:255',
        ];

        $validator = Validator::make($req->all(), $rules);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 422);
        }

        $empleado = new Empleado();

        if ($req->filled('id'))       $empleado->id       = $req->id;
        if ($req->filled('name'))     $empleado->name     = $req->name;
        if ($req->filled('username')) $empleado->username = $req->username;
        if ($req->filled('phone'))    $empleado->phone    = $req->phone;
        if ($req->filled('gender'))   $empleado->gender   = $req->gender;
        if ($req->filled('search'))   $empleado->search   = $req->search;

        if ($req->has('active')) {
            $empleado->active = $req->active;
        }

        $result = $empleado->consultar();

        if ($result === false) {
            return $this->sendError('Error al consultar los empleados', 500);
        }

        return $this->sendResponse($result, 'Empleados consultados exitosamente');
    }
    
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

    // ... Get, Create, GroupByCampo (ya los tienes)

    /**
     * Actualizar empleado.
     * PUT/POST /api/empleados/{id}
     */
    public function Update(Request $req, $id){
        $empleado = new Empleado();

        // Verificar que existe
        if (!$empleado->existe($id)) {
            return $this->sendError('Empleado no encontrado', 404);
        }

        $rules = [
            'role_id'  => 'required|integer|exists:roles,id',
            'name'     => 'required|string|min:3|max:255',
            'username' => 'required|string|min:3|max:255|unique:users,username,' . $id,
            'password' => 'nullable|string|min:6|max:255',
            'phone'    => 'required|string|min:10|max:10',
            'gender'   => 'required|string|in:M,F,O',
            'active'   => 'required|boolean',
        ];

        $messages = [
            'role_id.required'  => 'El campo Rol es requerido',
            'role_id.exists'    => 'El Rol seleccionado no existe',
            'name.required'     => 'El campo Nombre es requerido',
            'name.min'          => 'El Nombre debe tener al menos 3 caracteres',
            'username.required' => 'El campo Usuario es requerido',
            'username.unique'   => 'Ese nombre de usuario ya está registrado',
            'password.min'      => 'La Contraseña debe tener al menos 6 caracteres',
            'phone.required'    => 'El campo Teléfono es requerido',
            'phone.min'         => 'El Teléfono debe tener exactamente 10 caracteres',
            'phone.max'         => 'El Teléfono debe tener exactamente 10 caracteres',
            'gender.required'   => 'El campo Sexo es requerido',
            'gender.in'         => 'El Sexo debe ser M, F u O',
            'active.required'   => 'El campo Activo es requerido',
            'active.boolean'    => 'El Activo debe ser 0 o 1',
        ];

        $validator = Validator::make($req->all(), $rules, $messages);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 422);
        }

        $data = [
            'role_id'  => $req->role_id,
            'name'     => $req->name,
            'username' => $req->username,
            'phone'    => $req->phone,
            'gender'   => $req->gender,
            'active'   => $req->active,
        ];

        // 👇 Password es opcional: solo si viene con valor
        if ($req->filled('password')) {
            $data['password'] = $req->password;
        }

        $ok = $empleado->actualizar($id, $data);

        if ($ok === false) {
            return $this->sendError('Error al actualizar el empleado', 500);
        }

        return $this->sendResponse(null, 'Empleado actualizado exitosamente');
    }

    /**
     * Eliminar empleado.
     * DELETE /api/empleados/{id}
     */
    public function Delete($id){
        $empleado = new Empleado();

        if (!$empleado->existe($id)) {
            return $this->sendError('Empleado no encontrado', 404);
        }

        $ok = $empleado->eliminar($id);

        if ($ok === false) {
            return $this->sendError('Error al eliminar el empleado', 500);
        }

        return $this->sendResponse(null, 'Empleado eliminado exitosamente');
    }

    public function ListaSimple(Request $req){
    $empleado = new Empleado();
    $result = $empleado->listaSimple();

    if ($result === false) {
        return $this->sendError('Error al consultar la lista de empleados', 500);
    }

    return $this->sendResponse($result, 'Lista de empleados consultada exitosamente');
}

    public function GroupByCampo(Request $request){
        $validator = Validator::make($request->all(), [
            "action" => "required|string|in:roles",
        ]);

        $campo = $request->input('action');

        $result = Empleado::GroupbyCampo($campo);

        if ($result === false) {
            return $this->sendError('Error al consultar los datos agrupados');
        }

        return $this->sendResponse($result, "Datos {$campo} obtenidos");
    }
}
