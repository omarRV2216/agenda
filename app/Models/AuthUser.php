<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuthUser extends Model
{
    use HasFactory;

    protected $table = 'users';

    public function buscarPorUsername($username)
    {
        $sql = "SELECT 
                    u.id,
                    u.role_id,
                    u.name,
                    u.username,
                    u.password,
                    u.phone,
                    u.gender,
                    u.active,
                    r.name AS role_name,
                    r.display_name AS role_display_name
                FROM users u
                INNER JOIN roles r ON u.role_id = r.id
                WHERE u.username = :username
                LIMIT 1";

        try {
            $result = DB::select($sql, ['username' => $username]);

            if (empty($result)) {
                return null;
            }

            return $result[0];

        } catch (\Exception $e) {
            Log::error('Error en buscarPorUsername: ' . $e->getMessage());
            return null;
        }
    }
}