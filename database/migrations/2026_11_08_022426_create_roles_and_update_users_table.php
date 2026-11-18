<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: Control de Roles y Datos Universitarios (Doctor ULS)
 * Documento Sección 6: Cuatro roles (Visitante, Cliente, Doctor, Administrador).
 * Documento Sección 13: Validación e identificación obligatoria por carné (ej. AS251106).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Tabla para definir los 4 roles del sistema ULS
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique(); // admin, doctor, cliente
            $table->string('descripcion')->nullable();
            $table->timestamps();
        });

        // Actualización de la tabla de usuarios existente
        Schema::table('users', function (Blueprint $table) {
            // Carné universitario (estudiantes / docentes / personal)
            $table->string('carnet')->nullable()->after('email');

            // Llave foránea hacia la tabla de roles (Por defecto ID 3 = Cliente/Estudiante)
            $table->foreignId('role_id')->default(3)->after('id')->constrained('roles');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn(['role_id', 'carnet']);
        });
        Schema::dropIfExists('roles');
    }
};
