<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BusinessClosure extends Model
{
    use HasFactory;

    public $id;
    public $date;
    public $type;
    public $open_time;
    public $close_time;
    public $reason;

    public function consultar()
    {
        $sql = "SELECT id, date, type, open_time, close_time, reason, created_at
                FROM business_closures
                ORDER BY date ASC";

        try {
            return DB::select($sql);
        } catch (\Exception $e) {
            Log::error('Error consultar closures: ' . $e->getMessage());
            return false;
        }
    }

    public function porFecha($date)
    {
        $sql = "SELECT id, date, type, open_time, close_time, reason
                FROM business_closures
                WHERE date = ?";

        try {
            $result = DB::select($sql, [$date]);
            return $result[0] ?? null;
        } catch (\Exception $e) {
            Log::error('Error porFecha closure: ' . $e->getMessage());
            return null;
        }
    }

    public function crear($data)
    {
        if ($this->existe($data['date'])) {
            return "Ya existe una excepción para esa fecha";
        }

        $sql = "INSERT INTO business_closures
                (date, type, open_time, close_time, reason, created_at, updated_at)
                VALUES
                (:date, :type, :open_time, :close_time, :reason, NOW(), NOW())";

        try {
            DB::insert($sql, [
                'date'       => $data['date'],
                'type'       => $data['type'],
                'open_time'  => $data['open_time'] ?? null,
                'close_time' => $data['close_time'] ?? null,
                'reason'     => $data['reason'] ?? null,
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('Error crear closure: ' . $e->getMessage());
            return false;
        }
    }

    public function existe($date)
    {
        $sql = "SELECT COUNT(*) as total FROM business_closures WHERE date = ?";

        try {
            $result = DB::select($sql, [$date]);
            return $result[0]->total > 0;
        } catch (\Exception $e) {
            Log::error('Error existe closure: ' . $e->getMessage());
            return false;
        }
    }

    public function eliminar($id)
    {
        $sql = "DELETE FROM business_closures WHERE id = :id";

        try {
            DB::delete($sql, ['id' => $id]);
            return true;
        } catch (\Exception $e) {
            Log::error('Error eliminar closure: ' . $e->getMessage());
            return false;
        }
    }
}