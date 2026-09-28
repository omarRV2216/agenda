<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeService extends Model
{
    use HasFactory;

    public $id;
    public $employee_id;
    public $service_id;

    // ─────────────────────────────────────────────
    // EMPLEADOS QUE HACEN UN SERVICIO
    // ─────────────────────────────────────────────
    public function empleadosPorServicio($serviceId)
    {
        $sql = "SELECT
                    u.id,
                    u.name,
                    u.username,
                    u.phone,
                    u.gender
                FROM employee_services es
                INNER JOIN users u ON es.employee_id = u.id
                WHERE es.service_id = ?
                  AND u.active = 1
                ORDER BY u.name ASC";

        try {
            return DB::select($sql, [$serviceId]);
        } catch (\Exception $e) {
            Log::error('Error empleadosPorServicio: ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────
    // SERVICIOS QUE HACE UN EMPLEADO
    // ─────────────────────────────────────────────
    public function serviciosPorEmpleado($employeeId)
    {
        $sql = "SELECT
                    s.id,
                    s.name,
                    s.description,
                    s.price,
                    s.duration_minutes,
                    s.photo_path,
                    s.active
                FROM employee_services es
                INNER JOIN services s ON es.service_id = s.id
                WHERE es.employee_id = ?
                ORDER BY s.name ASC";

        try {
            return DB::select($sql, [$employeeId]);
        } catch (\Exception $e) {
            Log::error('Error serviciosPorEmpleado: ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────
    // ASIGNAR UN SERVICIO A UN EMPLEADO
    // ─────────────────────────────────────────────
    public function asignar($employeeId, $serviceId)
    {
        // Verificar que no exista ya
        if ($this->existe($employeeId, $serviceId)) {
            return "El empleado ya tiene asignado ese servicio";
        }

        $sql = "INSERT INTO employee_services (employee_id, service_id, created_at, updated_at)
                VALUES (:employee_id, :service_id, NOW(), NOW())";

        try {
            DB::insert($sql, [
                'employee_id' => $employeeId,
                'service_id'  => $serviceId,
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('Error asignar: ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────
    // QUITAR UN SERVICIO A UN EMPLEADO
    // ─────────────────────────────────────────────
    public function quitar($employeeId, $serviceId)
    {
        $sql = "DELETE FROM employee_services
                WHERE employee_id = :employee_id
                  AND service_id = :service_id";

        try {
            DB::delete($sql, [
                'employee_id' => $employeeId,
                'service_id'  => $serviceId,
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('Error quitar: ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────
    // REEMPLAZAR TODOS LOS SERVICIOS DE UN EMPLEADO
    // (útil para el panel de asignación masiva)
    // ─────────────────────────────────────────────
    public function reemplazar($employeeId, array $serviceIds)
    {
        try {
            DB::beginTransaction();

            // 1) Borrar los actuales
            DB::delete(
                "DELETE FROM employee_services WHERE employee_id = ?",
                [$employeeId]
            );

            // 2) Insertar los nuevos
            if (!empty($serviceIds)) {
                foreach ($serviceIds as $serviceId) {
                    DB::insert(
                        "INSERT INTO employee_services (employee_id, service_id, created_at, updated_at)
                         VALUES (?, ?, NOW(), NOW())",
                        [$employeeId, $serviceId]
                    );
                }
            }

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error reemplazar: ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────
    // EXISTE
    // ─────────────────────────────────────────────
    public function existe($employeeId, $serviceId)
    {
        $sql = "SELECT COUNT(*) as total
                FROM employee_services
                WHERE employee_id = :employee_id
                  AND service_id = :service_id";

        try {
            $result = DB::select($sql, [
                'employee_id' => $employeeId,
                'service_id'  => $serviceId,
            ]);
            return $result[0]->total > 0;
        } catch (\Exception $e) {
            Log::error('Error existe: ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────
    // VALIDAR QUE UN EMPLEADO HACE UN SERVICIO
    // (útil antes de crear una cita)
    // ─────────────────────────────────────────────
    public function empleadoHaceServicio($employeeId, $serviceId)
    {
        return $this->existe($employeeId, $serviceId);
    }

    // ─────────────────────────────────────────────
    // EMPLEADOS LIBRES PARA UN SERVICIO + FECHA + HORA
    // (asignación automática "cualquiera disponible")
    // ─────────────────────────────────────────────
    public function empleadosLibres($serviceId, $start, $end)
    {
        $sql = "SELECT
                    u.id,
                    u.name
                FROM users u
                INNER JOIN employee_services es ON es.employee_id = u.id
                WHERE es.service_id = :service_id
                  AND u.active = 1
                  AND u.id NOT IN (
                      SELECT a.employee_id
                      FROM appointments a
                      WHERE a.deleted_at IS NULL
                        AND a.status NOT IN ('cancelled', 'no_show')
                        AND a.start_time < :end
                        AND a.end_time   > :start
                  )
                ORDER BY u.name ASC";

        try {
            return DB::select($sql, [
                'service_id' => $serviceId,
                'start'      => $start,
                'end'        => $end,
            ]);
        } catch (\Exception $e) {
            Log::error('Error empleadosLibres: ' . $e->getMessage());
            return false;
        }
    }
}