<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Appointment extends Model
{
    use HasFactory;

    public $id;
    public $service_id;
    public $employee_id;
    public $created_by;
    public $client_name;
    public $client_phone;
    public $client_email;
    public $appointment_date;
    public $start_time;
    public $end_time;
    public $duration_minutes;
    public $service_name;
    public $service_price;
    public $status;
    public $notes;
    public $color;

    // Filtros de búsqueda
    public $date;
    public $from;
    public $to;
    public $search;

    // ─────────────────────────────────────────────
    // CONSULTAR
    // ─────────────────────────────────────────────
    public function consultar()
    {
        $filtro = "";
        $bindings = [];

        $sql = "SELECT
                    a.id,
                    a.service_id,
                    a.employee_id,
                    a.created_by,
                    a.client_name,
                    a.client_phone,
                    a.client_email,
                    a.appointment_date,
                    a.start_time,
                    a.end_time,
                    a.duration_minutes,
                    a.service_name,
                    a.service_price,
                    a.status,
                    a.notes,
                    a.color,
                    a.created_at,
                    a.updated_at,
                    u.name      AS employee_name,
                    u.username  AS employee_username,
                    s.photo_path AS service_photo
                FROM appointments a
                INNER JOIN users u ON a.employee_id = u.id
                INNER JOIN services s ON a.service_id = s.id
                WHERE a.deleted_at IS NULL";

        // ─── Filtro por id ───
        if (!empty($this->id)) {
            $filtro .= " AND a.id = ?";
            $bindings[] = $this->id;
        }

        // ─── Filtro por service_id ───
        if (!empty($this->service_id)) {
            $filtro .= " AND a.service_id = ?";
            $bindings[] = $this->service_id;
        }

        // ─── Filtro por employee_id ───
        if (!empty($this->employee_id)) {
            $filtro .= " AND a.employee_id = ?";
            $bindings[] = $this->employee_id;
        }

        // ─── Filtro por client_name (por palabras) ───
        if (!empty($this->client_name)) {
            $palabras = explode(' ', trim($this->client_name));
            $condiciones = [];

            foreach ($palabras as $palabra) {
                $palabra = trim($palabra);
                if ($palabra !== '') {
                    $condiciones[] = "a.client_name LIKE ?";
                    $bindings[] = "%" . $palabra . "%";
                }
            }

            if (!empty($condiciones)) {
                $filtro .= " AND (" . implode(" AND ", $condiciones) . ")";
            }
        }

        // ─── Filtro por client_phone ───
        if (!empty($this->client_phone)) {
            $filtro .= " AND a.client_phone LIKE ?";
            $bindings[] = "%" . $this->client_phone . "%";
        }

        // ─── Filtro por status ───
        if (!empty($this->status)) {
            $filtro .= " AND a.status = ?";
            $bindings[] = $this->status;
        }

        // ─── Filtro por fecha exacta ───
        if (!empty($this->date)) {
            $filtro .= " AND a.appointment_date = ?";
            $bindings[] = $this->date;
        }

        // ─── Filtro por rango de fechas ───
        if (!empty($this->from) && !empty($this->to)) {
            $filtro .= " AND a.appointment_date BETWEEN ? AND ?";
            $bindings[] = $this->from;
            $bindings[] = $this->to;
        }

        // ─── Búsqueda libre (cliente O teléfono O servicio) ───
        if (!empty($this->search)) {
            $filtro .= " AND (a.client_name LIKE ? OR a.client_phone LIKE ? OR a.service_name LIKE ?)";
            $like = "%" . $this->search . "%";
            $bindings[] = $like;
            $bindings[] = $like;
            $bindings[] = $like;
        }

        // ─── Ensamblar SQL final ───
        $sql .= $filtro;

        $sql .= " ORDER BY a.appointment_date ASC, a.start_time ASC";

        try {
            return DB::select($sql, $bindings);
        } catch (\Exception $e) {
            Log::error('Error consultar citas: ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────
    // CREAR
    // ─────────────────────────────────────────────
    public function Create_appointment()
    {
        try {
            // 1) Obtener datos del servicio (duración, precio, nombre)
            $servicio = DB::select(
                "SELECT name, price, duration_minutes 
                 FROM services 
                 WHERE id = ? AND active = 1",
                [$this->service_id]
            );

            if (empty($servicio)) {
                return "El servicio seleccionado no está disponible";
            }

            $serviceName     = $servicio[0]->name;
            $servicePrice    = $servicio[0]->price;
            $durationMinutes = $servicio[0]->duration_minutes;

            // 2) Calcular end_time
            $startDateTime = $this->appointment_date . ' ' . $this->start_time . ':00';
            $endDateTime   = date(
                'Y-m-d H:i:s',
                strtotime($startDateTime) + ($durationMinutes * 60)
            );

            // 3) Validar solapamiento con otras citas del mismo empleado
            if ($this->haySolapamiento(
                $this->employee_id,
                $startDateTime,
                $endDateTime,
                null
            )) {
                return "El horario seleccionado ya está ocupado para este empleado";
            }

            // 4) Insertar
            $sql = "INSERT INTO appointments (
                        service_id,
                        employee_id,
                        created_by,
                        client_name,
                        client_phone,
                        client_email,
                        appointment_date,
                        start_time,
                        end_time,
                        duration_minutes,
                        service_name,
                        service_price,
                        status,
                        notes,
                        created_at,
                        updated_at
                    ) VALUES (
                        :service_id,
                        :employee_id,
                        :created_by,
                        :client_name,
                        :client_phone,
                        :client_email,
                        :appointment_date,
                        :start_time,
                        :end_time,
                        :duration_minutes,
                        :service_name,
                        :service_price,
                        :status,
                        :notes,
                        NOW(),
                        NOW()
                    )";

            DB::insert($sql, [
                'service_id'       => $this->service_id,
                'employee_id'      => $this->employee_id,
                'created_by'       => $this->created_by,
                'client_name'      => $this->client_name,
                'client_phone'     => $this->client_phone ?? '',
                'client_email'     => $this->client_email ?? '',
                'appointment_date' => $this->appointment_date,
                'start_time'       => $startDateTime,
                'end_time'         => $endDateTime,
                'duration_minutes' => $durationMinutes,
                'service_name'     => $serviceName,
                'service_price'    => $servicePrice,
                'status'           => 'pending',
                'notes'            => $this->notes ?? '',
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Error Create_appointment: ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────
    // VALIDAR SOLAPAMIENTO
    // ─────────────────────────────────────────────
    public function haySolapamiento($employeeId, $start, $end, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as total
                FROM appointments
                WHERE employee_id = :employee_id
                  AND deleted_at IS NULL
                  AND status NOT IN ('cancelled', 'no_show')
                  AND start_time < :end
                  AND end_time   > :start";

        $bindings = [
            'employee_id' => $employeeId,
            'start'       => $start,
            'end'         => $end,
        ];

        // Si estamos actualizando, excluir la cita actual
        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $bindings['exclude_id'] = $excludeId;
        }

        try {
            $result = DB::select($sql, $bindings);
            return $result[0]->total > 0;
        } catch (\Exception $e) {
            Log::error('Error haySolapamiento: ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────
    // EXISTE
    // ─────────────────────────────────────────────
    public function existe($id)
    {
        $sql = "SELECT COUNT(*) as total 
                FROM appointments 
                WHERE id = :id AND deleted_at IS NULL";

        try {
            $result = DB::select($sql, ['id' => $id]);
            return $result[0]->total > 0;
        } catch (\Exception $e) {
            Log::error('Error existe cita: ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────
    // ACTUALIZAR
    // ─────────────────────────────────────────────
    public function actualizar($id, $data)
    {
        try {
            // 1) Obtener datos del servicio (por si cambió)
            $servicio = DB::select(
                "SELECT name, price, duration_minutes 
                 FROM services 
                 WHERE id = ? AND active = 1",
                [$data['service_id']]
            );

            if (empty($servicio)) {
                return "El servicio seleccionado no está disponible";
            }

            $serviceName     = $servicio[0]->name;
            $servicePrice    = $servicio[0]->price;
            $durationMinutes = $servicio[0]->duration_minutes;

            // 2) Calcular end_time
            $startDateTime = $data['appointment_date'] . ' ' . $data['start_time'] . ':00';
            $endDateTime   = date(
                'Y-m-d H:i:s',
                strtotime($startDateTime) + ($durationMinutes * 60)
            );

            // 3) Validar solapamiento (excluyendo esta cita)
            if ($this->haySolapamiento(
                $data['employee_id'],
                $startDateTime,
                $endDateTime,
                $id
            )) {
                return "El horario seleccionado ya está ocupado para este empleado";
            }

            // 4) Update
            $sql = "UPDATE appointments SET
                        service_id       = :service_id,
                        employee_id      = :employee_id,
                        client_name      = :client_name,
                        client_phone     = :client_phone,
                        client_email     = :client_email,
                        appointment_date = :appointment_date,
                        start_time       = :start_time,
                        end_time         = :end_time,
                        duration_minutes = :duration_minutes,
                        service_name     = :service_name,
                        service_price    = :service_price,
                        status           = :status,
                        notes            = :notes,
                        updated_at       = NOW()
                    WHERE id = :id AND deleted_at IS NULL";

            DB::update($sql, [
                'service_id'       => $data['service_id'],
                'employee_id'      => $data['employee_id'],
                'client_name'      => $data['client_name'],
                'client_phone'     => $data['client_phone'] ?? '',
                'client_email'     => $data['client_email'] ?? '',
                'appointment_date' => $data['appointment_date'],
                'start_time'       => $startDateTime,
                'end_time'         => $endDateTime,
                'duration_minutes' => $durationMinutes,
                'service_name'     => $serviceName,
                'service_price'    => $servicePrice,
                'status'           => $data['status'],
                'notes'            => $data['notes'] ?? '',
                'id'               => $id,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Error actualizar cita: ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────
    // ELIMINAR (soft delete)
    // ─────────────────────────────────────────────
    public function eliminar($id)
    {
        $sql = "UPDATE appointments 
                SET deleted_at = NOW() 
                WHERE id = :id AND deleted_at IS NULL";

        try {
            DB::update($sql, ['id' => $id]);
            return true;
        } catch (\Exception $e) {
            Log::error('Error eliminar cita: ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────
    // CAMBIAR ESTADO
    // ─────────────────────────────────────────────
    public function cambiarEstado($id, $status)
    {
        $sql = "UPDATE appointments 
                SET status = :status, updated_at = NOW() 
                WHERE id = :id AND deleted_at IS NULL";

        try {
            DB::update($sql, [
                'id'     => $id,
                'status' => $status,
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('Error cambiarEstado: ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────
    // DISPONIBILIDAD (slots libres)
    // ─────────────────────────────────────────────
    public function disponibilidad($serviceId, $employeeId, $date)
    {
        try {
            // 1) Duración del servicio
            $servicio = DB::select(
                "SELECT duration_minutes 
                 FROM services 
                 WHERE id = ? AND active = 1",
                [$serviceId]
            );

            if (empty($servicio)) {
                return false;
            }

            $duration = (int) $servicio[0]->duration_minutes;

            // 2) Horario laboral (ajústalo a tu negocio)
            $horaApertura = 9;   // 09:00
            $horaCierre   = 20;  // 20:00

            // 3) Citas ya agendadas ese día para ese empleado
            $citas = DB::select(
                "SELECT start_time, end_time 
                 FROM appointments 
                 WHERE employee_id = :employee_id
                   AND appointment_date = :date
                   AND deleted_at IS NULL
                   AND status NOT IN ('cancelled', 'no_show')
                 ORDER BY start_time ASC",
                [
                    'employee_id' => $employeeId,
                    'date'        => $date,
                ]
            );

            // 4) Generar slots cada 30 min y marcar disponibilidad
            $slots = [];
            $slotDuracion = 30; // granularidad en minutos

            $inicio = strtotime($date . ' ' . sprintf('%02d:00:00', $horaApertura));
            $fin    = strtotime($date . ' ' . sprintf('%02d:00:00', $horaCierre));

            for ($t = $inicio; $t + ($duration * 60) <= $fin; $t += ($slotDuracion * 60)) {
                $slotStart = date('Y-m-d H:i:s', $t);
                $slotEnd   = date('Y-m-d H:i:s', $t + ($duration * 60));

                $ocupado = false;
                foreach ($citas as $c) {
                    // Solapamiento
                    if ($slotStart < $c->end_time && $slotEnd > $c->start_time) {
                        $ocupado = true;
                        break;
                    }
                }

                $slots[] = [
                    'start'     => date('H:i', $t),
                    'end'       => date('H:i', $t + ($duration * 60)),
                    'available' => !$ocupado,
                ];
            }

            return [
                'date'             => $date,
                'duration_minutes' => $duration,
                'slots'            => $slots,
            ];

        } catch (\Exception $e) {
            Log::error('Error disponibilidad: ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────
    // PRÓXIMAS CITAS
    // ─────────────────────────────────────────────
    public function proximas($employeeId = null, $limit = 10)
    {
        try {
            $sql = "SELECT
                        a.id,
                        a.client_name,
                        a.client_phone,
                        a.appointment_date,
                        a.start_time,
                        a.end_time,
                        a.status,
                        a.service_name,
                        u.name AS employee_name
                    FROM appointments a
                    INNER JOIN users u ON a.employee_id = u.id
                    WHERE a.deleted_at IS NULL
                      AND a.status NOT IN ('cancelled', 'completed', 'no_show')
                      AND a.start_time >= NOW()";

            $bindings = [];

            if (!empty($employeeId)) {
                $sql .= " AND a.employee_id = ?";
                $bindings[] = $employeeId;
            }

            $sql .= " ORDER BY a.start_time ASC LIMIT " . (int) $limit;

            return DB::select($sql, $bindings);

        } catch (\Exception $e) {
            Log::error('Error proximas: ' . $e->getMessage());
            return false;
        }
    }
}