<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BusinessHour extends Model
{
    use HasFactory;

    public $id;
    public $day_of_week;
    public $is_open;
    public $open_time;
    public $close_time;

    public function consultar()
    {
        $sql = "SELECT id, day_of_week, is_open, open_time, close_time
                FROM business_hours
                ORDER BY day_of_week ASC";

        try {
            return DB::select($sql);
        } catch (\Exception $e) {
            Log::error('Error consultar business_hours: ' . $e->getMessage());
            return false;
        }
    }

    public function horarioDelDia($dayOfWeek)
    {
        $sql = "SELECT id, day_of_week, is_open, open_time, close_time
                FROM business_hours
                WHERE day_of_week = ?";

        try {
            $result = DB::select($sql, [$dayOfWeek]);
            return $result[0] ?? null;
        } catch (\Exception $e) {
            Log::error('Error horarioDelDia: ' . $e->getMessage());
            return null;
        }
    }

    public function guardarSemana(array $horarios)
    {
        try {
            DB::beginTransaction();

            foreach ($horarios as $h) {
                $dayOfWeek = $h['day_of_week'];
                $isOpen    = $h['is_open'] ? 1 : 0;

                $openTime  = $isOpen ? ($h['open_time'] ?? '09:00:00') : '09:00:00';
                $closeTime = $isOpen ? ($h['close_time'] ?? '20:00:00') : '20:00:00';

                DB::update(
                    "UPDATE business_hours
                     SET is_open = ?, open_time = ?, close_time = ?, updated_at = NOW()
                     WHERE day_of_week = ?",
                    [$isOpen, $openTime, $closeTime, $dayOfWeek]
                );
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error guardarSemana: ' . $e->getMessage());
            return false;
        }
    }
}