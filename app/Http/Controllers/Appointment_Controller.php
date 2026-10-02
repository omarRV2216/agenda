<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Appointment;

class Appointment_Controller extends AppBaseController{

    public function Get(Request $req){
        $rules = [
            'id'           => 'nullable|integer|min:1',
            'service_id'   => 'nullable|integer|min:1',
            'employee_id'  => 'nullable|integer|min:1',
            'client_name'  => 'nullable|string|min:1|max:150',
            'client_phone' => 'nullable|string|min:1|max:20',
            'status'       => 'nullable|string|in:pending,confirmed,in_progress,completed,cancelled,no_show',
            'date'         => 'nullable|date_format:Y-m-d',
            'from'         => 'nullable|date_format:Y-m-d',
            'to'           => 'nullable|date_format:Y-m-d',
            'search'       => 'nullable|string|min:1|max:255',
        ];

        $validator = Validator::make($req->all(), $rules);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 422);
        }

        // Validación cruzada: from y to van juntos
        if ($req->filled('from') xor $req->filled('to')) {
            return $this->sendError('Debes enviar "from" y "to" juntos', 422);
        }

        $appointment = new Appointment();

        if ($req->filled('id'))           $appointment->id           = $req->id;
        if ($req->filled('service_id'))   $appointment->service_id   = $req->service_id;
        if ($req->filled('employee_id'))  $appointment->employee_id  = $req->employee_id;
        if ($req->filled('client_name'))  $appointment->client_name  = $req->client_name;
        if ($req->filled('client_phone')) $appointment->client_phone = $req->client_phone;
        if ($req->filled('status'))       $appointment->status       = $req->status;
        if ($req->filled('date'))         $appointment->date         = $req->date;
        if ($req->filled('from'))         $appointment->from         = $req->from;
        if ($req->filled('to'))           $appointment->to           = $req->to;
        if ($req->filled('search'))       $appointment->search       = $req->search;

        $result = $appointment->consultar();

        if ($result === false) {
            return $this->sendError('Error al consultar las citas', 500);
        }

        return $this->sendResponse($result, 'Citas consultadas exitosamente');
    }

    public function Create(Request $request){
    $validator = Validator::make($request->all(), [
        'service_id'       => 'required|integer|exists:services,id',
        'employee_id'      => 'required|integer|exists:users,id',
        'client_name'      => 'required|string|min:3|max:150',
        'client_phone'     => 'nullable|string|max:20',
        'client_email'     => 'nullable|email|max:150',
        'appointment_date' => 'required|date_format:Y-m-d|after_or_equal:today',
        'start_time'       => 'required|date_format:H:i',
        'notes'            => 'nullable|string|max:1000',
    ], [
        // service_id
        'service_id.required'  => 'El campo Servicio es requerido',
        'service_id.integer'   => 'El campo Servicio debe ser un número entero',
        'service_id.exists'    => 'El Servicio seleccionado no existe',

        // employee_id
        'employee_id.required' => 'El campo Empleado es requerido',
        'employee_id.integer'  => 'El campo Empleado debe ser un número entero',
        'employee_id.exists'   => 'El Empleado seleccionado no existe',

        // client_name
        'client_name.required' => 'El campo Nombre del cliente es requerido',
        'client_name.string'   => 'El campo Nombre del cliente debe ser texto',
        'client_name.min'      => 'El campo Nombre del cliente debe tener al menos 3 caracteres',
        'client_name.max'      => 'El campo Nombre del cliente no debe exceder 150 caracteres',

        // client_phone
        'client_phone.string'  => 'El campo Teléfono debe ser texto',
        'client_phone.max'     => 'El campo Teléfono no debe exceder 20 caracteres',

        // client_email
        'client_email.email'   => 'El campo Email debe tener un formato válido',
        'client_email.max'     => 'El campo Email no debe exceder 150 caracteres',

        // appointment_date
        'appointment_date.required'       => 'El campo Fecha es requerido',
        'appointment_date.date_format'    => 'El campo Fecha debe tener formato YYYY-MM-DD',
        'appointment_date.after_or_equal' => 'La Fecha no puede ser en el pasado',

        // start_time
        'start_time.required'    => 'El campo Hora de inicio es requerido',
        'start_time.date_format' => 'El campo Hora debe tener formato HH:MM',

        // notes
        'notes.string' => 'El campo Notas debe ser texto',
        'notes.max'    => 'El campo Notas no debe exceder 1000 caracteres',
    ]);

    if ($validator->fails()) {
        return $this->sendError($validator->errors()->first());
    }

    $validated = $validator->validated();

    $appointment = new Appointment();

    foreach ($validated as $key => $value) {
        $appointment->$key = $value;
    }

    // 👇 CAMBIO AQUÍ
    // created_by: quién agenda la cita
    // - Portal público (sin token) → null
    // - Admin/colaborador logueado (con token) → user_id
    $user = auth('sanctum')->user();
    $appointment->created_by = $user?->id ?? null;

    try {
        $resultado = $appointment->Create_appointment();

        if ($resultado === false) {
            return $this->sendError('Error al crear la cita');
        }

        if (is_string($resultado)) {
            return $this->sendError($resultado);
        }

        return $this->sendSuccess("Cita creada exitosamente");

    } catch (\Exception $e) {
        return $this->sendError('Error: ' . $e->getMessage());
    }
}

    public function Update(Request $req, $id)
    {
        $appointment = new Appointment();

        // Verificar que existe
        if (!$appointment->existe($id)) {
            return $this->sendError('Cita no encontrada', 404);
        }

        $rules = [
            'service_id'       => 'required|integer|exists:services,id',
            'employee_id'      => 'required|integer|exists:users,id',
            'client_name'      => 'required|string|min:3|max:150',
            'client_phone'     => 'nullable|string|max:20',
            'client_email'     => 'nullable|email|max:150',
            'appointment_date' => 'required|date_format:Y-m-d',
            'start_time'       => 'required|date_format:H:i',
            'status'           => 'required|string|in:pending,confirmed,in_progress,completed,cancelled,no_show',
            'notes'            => 'nullable|string|max:1000',
        ];

        $messages = [
            'service_id.required'  => 'El campo Servicio es requerido',
            'service_id.exists'    => 'El Servicio seleccionado no existe',
            'employee_id.required' => 'El campo Empleado es requerido',
            'employee_id.exists'   => 'El Empleado seleccionado no existe',
            'client_name.required' => 'El campo Nombre del cliente es requerido',
            'client_name.min'      => 'El Nombre del cliente debe tener al menos 3 caracteres',
            'client_name.max'      => 'El Nombre del cliente no debe exceder 150 caracteres',
            'client_phone.max'     => 'El Teléfono no debe exceder 20 caracteres',
            'client_email.email'   => 'El Email debe tener un formato válido',
            'client_email.max'     => 'El Email no debe exceder 150 caracteres',
            'appointment_date.required'    => 'El campo Fecha es requerido',
            'appointment_date.date_format' => 'La Fecha debe tener formato YYYY-MM-DD',
            'start_time.required'    => 'El campo Hora de inicio es requerido',
            'start_time.date_format' => 'La Hora debe tener formato HH:MM',
            'status.required' => 'El campo Estado es requerido',
            'status.in'       => 'El Estado no es válido',
            'notes.max'       => 'Las Notas no deben exceder 1000 caracteres',
        ];

        $validator = Validator::make($req->all(), $rules, $messages);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 422);
        }

        $data = [
            'service_id'       => $req->service_id,
            'employee_id'      => $req->employee_id,
            'client_name'      => $req->client_name,
            'client_phone'     => $req->client_phone,
            'client_email'     => $req->client_email,
            'appointment_date' => $req->appointment_date,
            'start_time'       => $req->start_time,
            'status'           => $req->status,
            'notes'            => $req->notes,
        ];

        $resultado = $appointment->actualizar($id, $data);

        if ($resultado === false) {
            return $this->sendError('Error al actualizar la cita', 500);
        }

        // Si el modelo devuelve string → error de negocio (solapamiento)
        if (is_string($resultado)) {
            return $this->sendError($resultado, 409);
        }

        return $this->sendResponse(null, 'Cita actualizada exitosamente');
    }

    public function Delete($id)
    {
        $appointment = new Appointment();

        if (!$appointment->existe($id)) {
            return $this->sendError('Cita no encontrada', 404);
        }

        $ok = $appointment->eliminar($id);

        if ($ok === false) {
            return $this->sendError('Error al eliminar la cita', 500);
        }

        return $this->sendResponse(null, 'Cita eliminada exitosamente');
    }

    public function ChangeStatus(Request $req, $id)
    {
        $appointment = new Appointment();

        if (!$appointment->existe($id)) {
            return $this->sendError('Cita no encontrada', 404);
        }

        $validator = Validator::make($req->all(), [
            'status' => 'required|string|in:pending,confirmed,in_progress,completed,cancelled,no_show',
        ], [
            'status.required' => 'El campo Estado es requerido',
            'status.in'       => 'El Estado no es válido',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 422);
        }

        $ok = $appointment->cambiarEstado($id, $req->status);

        if ($ok === false) {
            return $this->sendError('Error al cambiar el estado', 500);
        }

        return $this->sendResponse(null, 'Estado actualizado exitosamente');
    }

    public function Availability(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'service_id'  => 'required|integer|exists:services,id',
            'employee_id' => 'required|integer|exists:users,id',
            'date'        => 'required|date_format:Y-m-d|after_or_equal:today',
        ], [
            'service_id.required'  => 'El campo Servicio es requerido',
            'service_id.exists'    => 'El Servicio seleccionado no existe',
            'employee_id.required' => 'El campo Empleado es requerido',
            'employee_id.exists'   => 'El Empleado seleccionado no existe',
            'date.required'        => 'El campo Fecha es requerido',
            'date.date_format'     => 'El campo Fecha debe tener formato YYYY-MM-DD',
            'date.after_or_equal'  => 'La Fecha no puede ser en el pasado',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 422);
        }

        $appointment = new Appointment();

        $result = $appointment->disponibilidad(
            $req->service_id,
            $req->employee_id,
            $req->date
        );

        if ($result === false) {
            return $this->sendError('Error al consultar disponibilidad', 500);
        }

        return $this->sendResponse($result, 'Disponibilidad consultada exitosamente');
    }

    public function Upcoming(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'employee_id' => 'nullable|integer|min:1',
            'limit'       => 'nullable|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 422);
        }

        $appointment = new Appointment();

        $result = $appointment->proximas(
            $req->employee_id ?? null,
            $req->limit ?? 10
        );

        if ($result === false) {
            return $this->sendError('Error al consultar próximas citas', 500);
        }

        return $this->sendResponse($result, 'Próximas citas consultadas');
    }
}