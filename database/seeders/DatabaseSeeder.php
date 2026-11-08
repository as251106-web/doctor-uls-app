<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;
use App\Models\Service;
use Illuminate\Support\Facades\Hash;

/**
 * SEEDER PRINCIPAL: Carga de Datos Iniciales "Doctor ULS"
 * Integra a los integrantes del equipo (Gerson Alvarenga AS251106, Lic. Oscar Romero)
 * y los servicios médicos iniciales para pruebas.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Inserción de los 4 Roles Principales
        $adminRole = Role::create(['nombre' => 'admin', 'descripcion' => 'Administrador Total del Sistema']);
        $doctorRole = Role::create(['nombre' => 'doctor', 'descripcion' => 'Personal Médico de Consulta']);
        $clienteRole = Role::create(['nombre' => 'cliente', 'descripcion' => 'Estudiante o Personal Universitario']);

        // 2. Usuarios Iniciales con Carnés Oficiales
        User::create([
            'name' => 'Administrador ULS',
            'email' => 'admin@uls.edu.sv',
            'carnet' => 'admin001',
            'password' => Hash::make('uls12345'),
            'role_id' => $adminRole->id,
        ]);

        User::create([
            'name' => 'Lic. Oscar Romero (Doctor)',
            'email' => 'doctor@uls.edu.sv',
            'carnet' => 'DOC001',
            'password' => Hash::make('uls123'),
            'role_id' => $doctorRole->id,
        ]);

        User::create([
            'name' => 'Gerson Alvarenga',
            'email' => 'as251106@uls.edu.sv',
            'carnet' => 'AS251106',
            'password' => Hash::make('uls1234'),
            'role_id' => $clienteRole->id,
        ]);

        // 3. Catálogo de Servicios Médicos Primarios ULS
        Service::create([
            'nombre' => 'Consulta Médica General',
            'descripcion' => 'Atención primaria, evaluación preventiva y seguimiento de padecimientos.',
            'activo' => true
        ]);
        Service::create([
            'nombre' => 'Atención de Primeros Auxilios',
            'descripcion' => 'Curaciones, curación de heridas menores y atención rápida ante emergencias en el campus.',
            'activo' => true
        ]);
        Service::create([
            'nombre' => 'Orientación de Salud Mental',
            'descripcion' => 'Acompañamiento psicológico primario y control del estrés académico.',
            'activo' => true
        ]);
    }
}
