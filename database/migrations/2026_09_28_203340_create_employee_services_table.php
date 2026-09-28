<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_services', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('service_id');

            $table->timestamps();

            // Un empleado no puede tener el mismo servicio dos veces
            $table->unique(['employee_id', 'service_id'], 'unique_employee_service');

            // Índices para consultas rápidas
            $table->index('employee_id');
            $table->index('service_id');

            // Foreign keys
            $table->foreign('employee_id')
                  ->references('id')->on('users')
                  ->onDelete('cascade');

            $table->foreign('service_id')
                  ->references('id')->on('services')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_services');
    }
};