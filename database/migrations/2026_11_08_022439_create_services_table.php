<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: Servicios Médicos Primarios ULS
 *  El Doctor/Admin puede crear, editar o deshabilitar servicios.
 *  Catálogo de atención médica preventiva y primeros auxilios.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // Ej: Consulta General, Primeros Auxilios, Salud Mental
            $table->text('descripcion');
            $table->boolean('activo')->default(true); // Permite ocultar/mostrar servicios
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
