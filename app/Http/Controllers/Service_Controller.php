<?php

namespace App\Http\Controllers;

use App\Http\Controllers\AppBaseController;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class Service_Controller extends AppBaseController
{
    /**
     * Listar servicios con filtros opcionales.
     * GET /api/servicios
     */
    public function Get(Request $req)
    {
        $rules = [
            'id'     => 'nullable|integer|min:1',
            'name'   => 'nullable|string|min:1|max:255',
            'active' => 'nullable|boolean',
            'search' => 'nullable|string|min:1|max:255',
        ];

        $validator = Validator::make($req->all(), $rules);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 422);
        }

        $service = new Service();

        if ($req->filled('id'))     $service->id     = $req->id;
        if ($req->filled('name'))   $service->name   = $req->name;
        if ($req->filled('search')) $service->search = $req->search;

        if ($req->has('active')) {
            $service->active = $req->active;
        }

        $result = $service->consultar();

        if ($result === false) {
            return $this->sendError('Error al consultar los servicios', 500);
        }

        return $this->sendResponse($result, 'Servicios consultados exitosamente');
    }

    /**
     * Crear servicio.
     * POST /api/servicios
     */
    public function Create(Request $req)
    {
        $rules = [
            'name'             => 'required|string|min:3|max:255',
            'description'      => 'nullable|string|max:1000',
            'price'            => 'required|numeric|min:0|max:999999',
            'duration_minutes' => 'required|integer|min:15|max:480',
            'active'           => 'nullable|boolean',
            'photo'            => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ];

        $validator = Validator::make($req->all(), $rules, [
            'name.required'             => 'El nombre es requerido',
            'name.min'                  => 'El nombre debe tener al menos 3 caracteres',
            'price.required'            => 'El precio es requerido',
            'price.numeric'             => 'El precio debe ser un número',
            'duration_minutes.required' => 'La duración es requerida',
            'duration_minutes.min'      => 'La duración mínima es 15 minutos',
            'duration_minutes.max'      => 'La duración máxima es 480 minutos (8 horas)',
            'photo.image'               => 'El archivo debe ser una imagen',
            'photo.mimes'               => 'Solo se permiten jpeg, png, jpg o webp',
            'photo.max'                 => 'La imagen no debe pesar más de 2MB',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 422);
        }

        // Subir foto si viene
        $photoPath = null;
        if ($req->hasFile('photo')) {
            $photoPath = $req->file('photo')->store('services', 'public');
        }

        $service = new Service();

        $id = $service->insertar([
            'name'             => $req->name,
            'description'      => $req->description,
            'price'            => $req->price,
            'duration_minutes' => $req->duration_minutes,
            'photo_path'       => $photoPath,
            'active'           => $req->active ?? 1,
        ]);

        if ($id === false) {
            return $this->sendError('Error al crear el servicio', 500);
        }

        return $this->sendResponse(['id' => $id], 'Servicio creado exitosamente');
    }

    /**
     * Actualizar servicio.
     * PUT /api/servicios/{id}
     */
    public function Update(Request $req, $id)
    {
        $service = new Service();

        if (!$service->existe($id)) {
            return $this->sendError('Servicio no encontrado', 404);
        }

        $rules = [
            'name'             => 'required|string|min:3|max:255',
            'description'      => 'nullable|string|max:1000',
            'price'            => 'required|numeric|min:0|max:999999',
            'duration_minutes' => 'required|integer|min:15|max:480',
            'active'           => 'nullable|boolean',
            'photo'            => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ];

        $validator = Validator::make($req->all(), $rules);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 422);
        }

        $data = [
            'name'             => $req->name,
            'description'      => $req->description,
            'price'            => $req->price,
            'duration_minutes' => $req->duration_minutes,
            'active'           => $req->active ?? 1,
        ];

        // Si viene nueva foto, reemplazar
        if ($req->hasFile('photo')) {
            $data['photo_path'] = $req->file('photo')->store('services', 'public');
        }

        $ok = $service->actualizar($id, $data);

        if ($ok === false) {
            return $this->sendError('Error al actualizar el servicio', 500);
        }

        return $this->sendResponse(null, 'Servicio actualizado exitosamente');
    }

    /**
     * Desactivar servicio (soft delete).
     * PATCH /api/servicios/{id}/desactivar
     */
    public function Desactivar($id)
    {
        $service = new Service();

        if (!$service->existe($id)) {
            return $this->sendError('Servicio no encontrado', 404);
        }

        $ok = $service->desactivar($id);

        if ($ok === false) {
            return $this->sendError('Error al desactivar el servicio', 500);
        }

        return $this->sendResponse(null, 'Servicio desactivado');
    }

    /**
     * Eliminar servicio.
     * DELETE /api/servicios/{id}
     */
    public function Delete($id)
    {
        $service = new Service();

        if (!$service->existe($id)) {
            return $this->sendError('Servicio no encontrado', 404);
        }

        // Obtener photo_path para borrar la imagen
        $result = $service->consultar();
        // (Necesitas hacer un SELECT por id antes, ver abajo)

        $ok = $service->eliminar($id);

        if ($ok === false) {
            return $this->sendError('Error al eliminar el servicio', 500);
        }

        return $this->sendResponse(null, 'Servicio eliminado');
    }
}