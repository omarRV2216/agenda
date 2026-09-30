<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_closures', function (Blueprint $table) {
            $table->id();

            $table->date('date')->unique();

            // 'closed' = cerrado todo el día
            // 'custom' = abre con horario especial
            $table->enum('type', ['closed', 'custom']);

            // Solo se usan si type = 'custom'
            $table->time('open_time')->nullable();
            $table->time('close_time')->nullable();

            $table->string('reason', 150)->nullable();

            $table->timestamps();

            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_closures');
    }
};