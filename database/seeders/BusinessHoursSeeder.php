<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BusinessHoursSeeder extends Seeder
{
    public function run(): void
    {
        // Borrar datos previos (por si lo corres varias veces)
        DB::table('business_hours')->truncate();

        // Insertar los 7 días por defecto
        $dias = [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo',
        ];

        $rows = [];
        foreach ($dias as $dayNumber => $nombre) {
            $rows[] = [
                'day_of_week' => $dayNumber,
                'is_open'     => $dayNumber <= 6 ? 1 : 0, // Lun-Sáb abiertos, Dom cerrado
                'open_time'   => '09:00:00',
                'close_time'  => '20:00:00',
                'created_at'  => now(),
                'updated_at'  => now(),
            ];
        }

        DB::table('business_hours')->insert($rows);

        $this->command->info('✅ Horarios de negocio creados (Lun-Sáb 9-20, Dom cerrado)');
    }
}