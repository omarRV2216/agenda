<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('service_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('created_by')->nullable();

            $table->string('client_name', 150);
            $table->string('client_phone', 20)->nullable();
            $table->string('client_email', 150)->nullable();

            $table->date('appointment_date');
            $table->datetime('start_time');
            $table->datetime('end_time');
            $table->unsignedSmallInteger('duration_minutes');

            $table->string('service_name', 255);
            $table->decimal('service_price', 10, 2);

            $table->enum('status', [
                'pending',
                'confirmed',
                'in_progress',
                'completed',
                'cancelled',
                'no_show',
            ])->default('pending');

            $table->text('notes')->nullable();
            $table->string('color', 7)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('appointment_date');
            $table->index(['employee_id', 'appointment_date']);
            $table->index(['start_time', 'end_time']);
            $table->index('status');
            $table->index('service_id');

            $table->foreign('service_id')
                  ->references('id')->on('services')
                  ->onDelete('restrict');

            $table->foreign('employee_id')
                  ->references('id')->on('users')
                  ->onDelete('restrict');

            $table->foreign('created_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};