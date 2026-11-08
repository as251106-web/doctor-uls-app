<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: Módulo Central de Citas Médicas
 * Documento Sección 5 y 11: Control de flujo de citas, horarios y expedientes clínicos digitales.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            // Paciente de la comunidad ULS (Cliente)
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');

            // Doctor/Encargado que atiende la cita (Asignación opcional inicial)
            $table->foreignId('doctor_id')->nullable()->constrained('users')->onDelete('set null');

            // Tipo de servicio médico solicitado
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');

            // Programación temporal de la consulta
            $table->dateTime('fecha_hora');

            // Estado operativo de la cita
            $table->enum('estado', ['pendiente', 'confirmada', 'completada', 'cancelada'])->default('pendiente');

            // Motivo del padecimiento ingresado por el paciente
            $table->text('motivo')->nullable();

            // Expediente clínico / Anotaciones registradas por el doctor tras la consulta
            $table->text('observaciones_medicas')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
