<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\BusinessHour;
use App\Models\BusinessClosure;

class BusinessConfig_Controller extends AppBaseController
{
    /**
     * GET /business-config
     */
    public function Get(Request $req)
    {
        $hourModel    = new BusinessHour();
        $closureModel = new BusinessClosure();

        $horarios = $hourModel->consultar();
        $cierres  = $closureModel->consultar();

        if ($horarios === false || $cierres === false) {
            return $this->sendError('Error al consultar la configuración', 500);
        }

        return $this->sendResponse([
            'hours'    => $horarios,
            'closures' => $cierres,
        ], 'Configuración consultada exitosamente');
    }

    /**
     * POST /business-config/hours
     */
    public function UpdateHours(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'hours'                => 'required|array|size:7',
            'hours.*.day_of_week'  => 'required|integer|between:1,7',
            'hours.*.is_open'      => 'required|boolean',
            'hours.*.open_time'    => 'nullable|date_format:H:i:s',
            'hours.*.close_time'   => 'nullable|date_format:H:i:s',
        ], [
            'hours.required'                => 'Debes enviar los 7 días',
            'hours.size'                    => 'Debes enviar exactamente 7 días',
            'hours.*.day_of_week.required'  => 'Falta el día de la semana',
            'hours.*.day_of_week.between'   => 'El día debe estar entre 1 y 7',
            'hours.*.is_open.required'      => 'Falta el campo is_open',
            'hours.*.open_time.date_format' => 'La hora de apertura debe ser HH:MM:SS',
            'hours.*.close_time.date_format'=> 'La hora de cierre debe ser HH:MM:SS',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 422);
        }

        // Validación extra
        foreach ($req->hours as $h) {
            if ($h['is_open']) {
                if (empty($h['open_time']) || empty($h['close_time'])) {
                    return $this->sendError(
                        'Si el día está abierto, debes indicar hora de apertura y cierre',
                        422
                    );
                }

                if (strtotime($h['close_time']) <= strtotime($h['open_time'])) {
                    return $this->sendError(
                        'La hora de cierre debe ser posterior a la de apertura',
                        422
                    );
                }
            }
        }

        $model = new BusinessHour();
        $ok = $model->guardarSemana($req->hours);

        if ($ok === false) {
            return $this->sendError('Error al guardar el horario', 500);
        }

        return $this->sendSuccess('Horario guardado exitosamente');
    }

    /**
     * POST /business-config/closures
     */
    public function AddClosure(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'date'       => 'required|date_format:Y-m-d',
            'type'       => 'required|string|in:closed,custom',
            'open_time'  => 'required_if:type,custom|nullable|date_format:H:i:s',
            'close_time' => 'required_if:type,custom|nullable|date_format:H:i:s',
            'reason'     => 'nullable|string|max:150',
        ], [
            'date.required'         => 'La fecha es requerida',
            'date.date_format'      => 'La fecha debe tener formato YYYY-MM-DD',
            'type.required'         => 'El tipo es requerido',
            'type.in'               => 'El tipo debe ser closed o custom',
            'open_time.required_if' => 'Si el tipo es custom, la hora de apertura es requerida',
            'close_time.required_if'=> 'Si el tipo es custom, la hora de cierre es requerida',
            'reason.max'            => 'El motivo no debe exceder 150 caracteres',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 422);
        }

        if ($req->type === 'custom') {
            if (strtotime($req->close_time) <= strtotime($req->open_time)) {
                return $this->sendError(
                    'La hora de cierre debe ser posterior a la de apertura',
                    422
                );
            }
        }

        $model = new BusinessClosure();

        $data = [
            'date'       => $req->date,
            'type'       => $req->type,
            'open_time'  => $req->type === 'custom' ? $req->open_time : null,
            'close_time' => $req->type === 'custom' ? $req->close_time : null,
            'reason'     => $req->reason,
        ];

        $resultado = $model->crear($data);

        if ($resultado === false) {
            return $this->sendError('Error al agregar la excepción', 500);
        }

        if (is_string($resultado)) {
            return $this->sendError($resultado, 409);
        }

        return $this->sendSuccess('Excepción agregada exitosamente');
    }

    /**
     * DELETE /business-config/closures/{id}
     */
    public function RemoveClosure($id)
    {
        $model = new BusinessClosure();

        $ok = $model->eliminar($id);

        if ($ok === false) {
            return $this->sendError('Error al eliminar la excepción', 500);
        }

        return $this->sendSuccess('Excepción eliminada exitosamente');
    }
}