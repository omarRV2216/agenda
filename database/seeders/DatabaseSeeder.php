<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 👇 Agrega tu seeder aquí
        $this->call([
            BusinessHoursSeeder::class,
        ]);
    }
}