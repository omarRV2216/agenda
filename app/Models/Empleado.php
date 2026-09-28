<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;



class Empleado extends Model
{
    use HasFactory;
    public $id;
    public $role_id;
    public $name;
    public $username;
    public $password;
    public $phone;
    public $gender;
    public $active;
    public $search;

    public function consultar(){
        $filtro = "";
        $bindings = [];

        $sql = "SELECT 
                    u.id,
                    u.role_id,
                    u.name,
                    u.username,
                    u.phone,
                    u.birth_date,
                    u.gender,
                    u.active,
                    r.name AS role_name,
                    r.display_name AS role_display_name
                FROM users u
                INNER JOIN roles r ON u.role_id = r.id";

        // ─── Filtro por id ───
        if (!empty($this->id)) {
            if ($filtro != "") {
                $filtro .= " AND ";
            }
            $filtro .= "u.id = ?";
            $bindings[] = $this->id;
        }

        // ─── Filtro por role_id ───
        if (!empty($this->role_id)) {
            if ($filtro != "") {
                $filtro .= " AND ";
            }
            $filtro .= "u.role_id = ?";
            $bindings[] = $this->role_id;
        }

        // ─── Filtro por role_name ───
        if (!empty($this->role_name)) {
            if ($filtro != "") {
                $filtro .= " AND ";
            }
            $filtro .= "r.name = ?";
            $bindings[] = $this->role_name;
        }

        // ─── Filtro por nombre (por palabras) ───
        if (!empty($this->name)) {
            $palabras = explode(' ', trim($this->name));
            $condiciones = [];

            foreach ($palabras as $palabra) {
                $palabra = trim($palabra);
                if ($palabra !== '') {
                    $condiciones[] = "u.name LIKE ?";
                    $bindings[] = "%" . $palabra . "%";
                }
            }

            if (!empty($condiciones)) {
                if ($filtro != "") {
                    $filtro .= " AND ";
                }
                $filtro .= "(" . implode(" AND ", $condiciones) . ")";
            }
        }

        // ─── Filtro por username ───
        if (!empty($this->username)) {
            if ($filtro != "") {
                $filtro .= " AND ";
            }
            $filtro .= "u.username LIKE ?";
            $bindings[] = "%" . $this->username . "%";
        }

        // ─── Filtro por teléfono ───
        if (!empty($this->phone)) {
            if ($filtro != "") {
                $filtro .= " AND ";
            }
            $filtro .= "u.phone LIKE ?";
            $bindings[] = "%" . $this->phone . "%";
        }

        // ─── Filtro por género ───
        if (!empty($this->gender)) {
            if ($filtro != "") {
                $filtro .= " AND ";
            }
            $filtro .= "u.gender = ?";
            $bindings[] = $this->gender;
        }

        // ─── Filtro por activo ───
        if ($this->active !== null && $this->active !== '') {
            if ($filtro != "") {
                $filtro .= " AND ";
            }
            $filtro .= "u.active = ?";
            $bindings[] = $this->active;
        }

        // ─── Búsqueda libre (nombre O username O teléfono) ───
        if (!empty($this->search)) {
            if ($filtro != "") {
                $filtro .= " AND ";
            }
            $filtro .= "(u.name LIKE ? OR u.username LIKE ? OR u.phone LIKE ?)";
            $like = "%" . $this->search . "%";
            $bindings[] = $like;
            $bindings[] = $like;
            $bindings[] = $like;
        }

        // ─── Ensamblar SQL final ───
        if ($filtro != "") {
            $sql .= " WHERE " . $filtro;
        }

        $sql .= " ORDER BY u.name ASC";

        try {
            return DB::select($sql, $bindings);
        } catch (\Exception $e) {
            return false;
        }
    }


    public function Create_user(){
        $sql= "INSERT INTO users 
					(id, 
					role_id, 
					name, 
					username, 
					password, 
					phone, 
					gender, 
					active
                    )

					SELECT RIGHT(CONCAT('00000' , IFNULL(MAX(id),0)+1),6), 
					:role_id, 
					:name, 
					:username, 
					:password, 
					:phone, 
					:gender, 
					:active
					FROM users";
        
        try {
                        
            // Ejecutar el insert
            DB::insert($sql, [
                'role_id'         => $this->role_id,
                'name'           => $this->name,
                'username'           => $this->username,
                'password' => Hash::make($this->password),
                'phone'            => $this->phone ?? '',
                'gender'      => $this->gender ?? '',
                'active'      => $this->active ?? '',
            ]);
            
            return $sql;
            
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function GroupbyCampo($campo)
{
    $camposPermitidos = [
        'roles' => 'SELECT id, name, display_name FROM roles ORDER BY name',
    ];

    // Validar el campo
    if (!array_key_exists($campo, $camposPermitidos)) {
        return false;
    }

    $sql = $camposPermitidos[$campo];

    try {
        return DB::select($sql);
    } catch (\Exception $e) {
        return false;
    }
}
}
