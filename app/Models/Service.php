<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Service extends Model
{
    use HasFactory;

    protected $table = 'services';

    // Propiedades de filtro (para consultar)
    public $id;
    public $name;
    public $description;
    public $price;
    public $duration_minutes;
    public $active;
    public $search;

    /**
     * Consultar servicios con filtros dinámicos.
     */
    public function consultar()
    {
        $filtro = "";
        $bindings = [];

        $sql = "SELECT 
                    s.id,
                    s.name,
                    s.description,
                    s.price,
                    s.duration_minutes,
                    s.photo_path,
                    s.active,
                    s.created_at,
                    s.updated_at
                FROM services s";

        // Filtro por id
        if (!empty($this->id)) {
            if ($filtro != "") $filtro .= " AND ";
            $filtro .= "s.id = ?";
            $bindings[] = $this->id;
        }

        // Filtro por nombre (por palabras)
        if (!empty($this->name)) {
            $palabras = explode(' ', trim($this->name));
            $condiciones = [];

            foreach ($palabras as $palabra) {
                $palabra = trim($palabra);
                if ($palabra !== '') {
                    $condiciones[] = "s.name LIKE ?";
                    $bindings[] = "%" . $palabra . "%";
                }
            }

            if (!empty($condiciones)) {
                if ($filtro != "") $filtro .= " AND ";
                $filtro .= "(" . implode(" AND ", $condiciones) . ")";
            }
        }

        // Filtro por activo
        if ($this->active !== null && $this->active !== '') {
            if ($filtro != "") $filtro .= " AND ";
            $filtro .= "s.active = ?";
            $bindings[] = $this->active;
        }

        // Búsqueda libre (nombre o descripción)
        if (!empty($this->search)) {
            if ($filtro != "") $filtro .= " AND ";
            $filtro .= "(s.name LIKE ? OR s.description LIKE ?)";
            $like = "%" . $this->search . "%";
            $bindings[] = $like;
            $bindings[] = $like;
        }

        if ($filtro != "") {
            $sql .= " WHERE " . $filtro;
        }

        $sql .= " ORDER BY s.name DESC";

        try {
            return DB::select($sql, $bindings);
        } catch (\Exception $e) {
            Log::error('Error consultar servicios: ' . $e->getMessage());
            Log::error('SQL: ' . $sql);
            return false;
        }
    }

    /**
     * Crear servicio.
     */
    public function insertar($data){
        $sql = "INSERT INTO services 
                    (name, description, price, duration_minutes, photo_path, active, created_at, updated_at)
                VALUES 
                    (:name, :description, :price, :duration_minutes, :photo_path, :active, NOW(), NOW())";

        try {
            DB::insert($sql, [
                'name'             => $data['name'],
                'description'      => $data['description'] ?? null,
                'price'            => $data['price'],
                'duration_minutes' => $data['duration_minutes'],
                'photo_path'       => $data['photo_path'] ?? null,
                'active'           => $data['active'] ?? 1,
            ]);

            return DB::getPdo()->lastInsertId();
        } catch (\Exception $e) {
            Log::error('Error insertar servicio: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar servicio.
     */
    public function actualizar($id, $data){
        $campos = [];
        $bindings = [];

        if (isset($data['name'])) {
            $campos[] = "name = :name";
            $bindings['name'] = $data['name'];
        }

        if (array_key_exists('description', $data)) {
            $campos[] = "description = :description";
            $bindings['description'] = $data['description'];
        }

        if (isset($data['price'])) {
            $campos[] = "price = :price";
            $bindings['price'] = $data['price'];
        }

        if (isset($data['duration_minutes'])) {
            $campos[] = "duration_minutes = :duration_minutes";
            $bindings['duration_minutes'] = $data['duration_minutes'];
        }

        if (array_key_exists('photo_path', $data)) {
            $campos[] = "photo_path = :photo_path";
            $bindings['photo_path'] = $data['photo_path'];
        }

        if (isset($data['active'])) {
            $campos[] = "active = :active";
            $bindings['active'] = $data['active'];
        }

        if (empty($campos)) return false;

        $campos[] = "updated_at = NOW()";
        $bindings['id'] = $id;

        $sql = "UPDATE services SET " . implode(", ", $campos) . " WHERE id = :id";

        try {
            DB::update($sql, $bindings);
            return true;
        } catch (\Exception $e) {
            Log::error('Error actualizar servicio: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Desactivar servicio (soft delete).
     */
    public function desactivar($id)
    {
        $sql = "UPDATE services SET active = 0, updated_at = NOW() WHERE id = :id";

        try {
            DB::update($sql, ['id' => $id]);
            return true;
        } catch (\Exception $e) {
            Log::error('Error desactivar servicio: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar servicio (hard delete).
     */
    public function eliminar($id)
    {
        $sql = "DELETE FROM services WHERE id = :id";

        try {
            DB::delete($sql, ['id' => $id]);
            return true;
        } catch (\Exception $e) {
            Log::error('Error eliminar servicio: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si existe un servicio por id.
     */
    public function existe($id)
    {
        $sql = "SELECT COUNT(*) as total FROM services WHERE id = :id";

        try {
            $result = DB::select($sql, ['id' => $id]);
            return $result[0]->total > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
}