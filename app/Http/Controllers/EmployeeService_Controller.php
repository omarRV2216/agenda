<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\EmployeeService;

class EmployeeService_Controller extends AppBaseController
{
    /**
     * Empleados que hacen un servicio.
     * GET /services/{id}/employees
     */
    public function EmpleadosPorServicio($serviceId)
    {
        $validator = Validator::make(
            ['service_id' => $serviceId],
            ['service_id' => 'required|integer|exists:services,id'],
            [
                'service_id.required' => 'El campo Servicio es requerido',
                'service_id.integer'  => 'El Servicio debe ser un número entero',
                'service_id.exists'   => 'El Servicio seleccionado no existe',
            ]
        );

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 422);
        }

        $model = new EmployeeService();
        $result = $model->empleadosPorServicio($serviceId);

        if ($result === false) {
            return $this->sendError('Error al consultar los empleados del servicio', 500);
        }

        return $this->sendResponse($result, 'Empleados consultados exitosamente');
    }

    /**
     * Servicios que hace un empleado.
     * GET /employees/{id}/services
     */
    public function ServiciosPorEmpleado($employeeId)
    {
        $validator = Validator::make(
            ['employee_id' => $employeeId],
            ['employee_id' => 'required|integer|exists:users,id'],
            [
                'employee_id.required' => 'El campo Empleado es requerido',
                'employee_id.integer'  => 'El Empleado debe ser un número entero',
                'employee_id.exists'   => 'El Empleado seleccionado no existe',
            ]
        );

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 422);
        }

        $model = new EmployeeService();
        $result = $model->serviciosPorEmpleado($employeeId);

        if ($result === false) {
            return $this->sendError('Error al consultar los servicios del empleado', 500);
        }

        return $this->sendResponse($result, 'Servicios consultados exitosamente');
    }

    /**
     * Asignar un servicio a un empleado.
     * POST /employee-services/assign
     */
    public function Assign(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'employee_id' => 'required|integer|exists:users,id',
            'service_id'  => 'required|integer|exists:services,id',
        ], [
            'employee_id.required' => 'El campo Empleado es requerido',
            'employee_id.integer'  => 'El Empleado debe ser un número entero',
            'employee_id.exists'   => 'El Empleado seleccionado no existe',

            'service_id.required'  => 'El campo Servicio es requerido',
            'service_id.integer'   => 'El Servicio debe ser un número entero',
            'service_id.exists'    => 'El Servicio seleccionado no existe',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 422);
        }

        $model = new EmployeeService();

        try {
            $resultado = $model->asignar($req->employee_id, $req->service_id);

            if ($resultado === false) {
                return $this->sendError('Error al asignar el servicio', 500);
            }

            // Error de negocio: ya existe
            if (is_string($resultado)) {
                return $this->sendError($resultado, 409);
            }

            return $this->sendSuccess("Servicio asignado exitosamente");

        } catch (\Exception $e) {
            Log::error('Error Assign: ' . $e->getMessage());
            return $this->sendError('Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Quitar un servicio a un empleado.
     * POST /employee-services/remove
     */
    public function Remove(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'employee_id' => 'required|integer|exists:users,id',
            'service_id'  => 'required|integer|exists:services,id',
        ], [
            'employee_id.required' => 'El campo Empleado es requerido',
            'employee_id.exists'   => 'El Empleado seleccionado no existe',
            'service_id.required'  => 'El campo Servicio es requerido',
            'service_id.exists'    => 'El Servicio seleccionado no existe',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 422);
        }

        $model = new EmployeeService();

        try {
            $ok = $model->quitar($req->employee_id, $req->service_id);

            if ($ok === false) {
                return $this->sendError('Error al quitar el servicio', 500);
            }

            return $this->sendSuccess("Servicio removido exitosamente");

        } catch (\Exception $e) {
            Log::error('Error Remove: ' . $e->getMessage());
            return $this->sendError('Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Reemplazar todos los servicios de un empleado.
     * POST /employee-services/replace
     */
    public function Replace(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'employee_id' => 'required|integer|exists:users,id',
            'service_ids' => 'present|array',
            'service_ids.*' => 'integer|exists:services,id',
        ], [
            'employee_id.required'  => 'El campo Empleado es requerido',
            'employee_id.exists'    => 'El Empleado seleccionado no existe',
            'service_ids.present'   => 'El campo Servicios es requerido',
            'service_ids.array'     => 'El campo Servicios debe ser un arreglo',
            'service_ids.*.integer' => 'Cada Servicio debe ser un número entero',
            'service_ids.*.exists'  => 'Uno de los Servicios seleccionados no existe',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 422);
        }

        $model = new EmployeeService();

        try {
            $ok = $model->reemplazar($req->employee_id, $req->service_ids ?? []);

            if ($ok === false) {
                return $this->sendError('Error al reemplazar los servicios', 500);
            }

            return $this->sendSuccess("Servicios actualizados exitosamente");

        } catch (\Exception $e) {
            Log::error('Error Replace: ' . $e->getMessage());
            return $this->sendError('Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Empleados libres para un servicio + rango horario.
     * POST /employee-services/available
     */
    public function Available(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'service_id' => 'required|integer|exists:services,id',
            'start'      => 'required|date_format:Y-m-d H:i:s',
            'end'        => 'required|date_format:Y-m-d H:i:s|after:start',
        ], [
            'service_id.required'  => 'El campo Servicio es requerido',
            'service_id.exists'    => 'El Servicio seleccionado no existe',
            'start.required'       => 'El campo Inicio es requerido',
            'start.date_format'    => 'El Inicio debe tener formato YYYY-MM-DD HH:MM:SS',
            'end.required'         => 'El campo Fin es requerido',
            'end.date_format'      => 'El Fin debe tener formato YYYY-MM-DD HH:MM:SS',
            'end.after'            => 'El Fin debe ser posterior al Inicio',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 422);
        }

        $model = new EmployeeService();
        $result = $model->empleadosLibres($req->service_id, $req->start, $req->end);

        if ($result === false) {
            return $this->sendError('Error al consultar empleados libres', 500);
        }

        return $this->sendResponse($result, 'Empleados libres consultados exitosamente');
    }
}