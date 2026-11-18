<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * SEEDER PRINCIPAL: Carga de Datos Iniciales "Doctor ULS"
 * Integra usuarios de ejemplo y servicios médicos iniciales para pruebas.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Inserción de los 4 Roles Principales
        $adminRole = Role::firstOrCreate(
            ['nombre' => 'admin'],
            ['descripcion' => 'Administrador Total del Sistema'],
        );
        $doctorRole = Role::firstOrCreate(
            ['nombre' => 'doctor'],
            ['descripcion' => 'Personal Médico de Consulta'],
        );
        $clienteRole = Role::firstOrCreate(
            ['nombre' => 'cliente'],
            ['descripcion' => 'Estudiante o Personal Universitario'],
        );

        // 2. Usuarios Iniciales con Carnés Oficiales
        User::firstOrCreate(
            ['email' => 'admin@uls.edu.sv'],
            [
                'name' => 'Administrador ULS',
                'carnet' => 'admin001',
                'password' => Hash::make('uls12345'),
                'role_id' => $adminRole->id,
            ],
        );

        $firstDoctor = User::firstOrCreate(
            ['email' => 'doctor@uls.edu.sv'],
            [
                'name' => 'Dr. Mateo Salazar',
                'carnet' => 'DOC001',
                'password' => Hash::make('uls123'),
                'role_id' => $doctorRole->id,
            ],
        );
        $firstDoctor->update([
            'name' => 'Dr. Mateo Salazar',
            'role_id' => $doctorRole->id,
        ]);

        $secondDoctor = User::firstOrCreate(
            ['email' => 'ana.lopez@example.test'],
            [
                'name' => 'Dra. Ana López',
                'carnet' => 'DOC002',
                'password' => Hash::make('ulsA1234'),
                'role_id' => $doctorRole->id,
            ],
        );
        $secondDoctor->update(['role_id' => $doctorRole->id]);

        $thirdDoctor = User::firstOrCreate(
            ['email' => 'carlos.mendez@example.test'],
            [
                'name' => 'Dr. Carlos Méndez',
                'carnet' => 'DOC003',
                'password' => Hash::make('ulsB1234'),
                'role_id' => $doctorRole->id,
            ],
        );
        $thirdDoctor->update(['role_id' => $doctorRole->id]);

        User::firstOrCreate(
            ['email' => 'as251106@uls.edu.sv'],
            [
                'name' => 'Gerson Alvarenga',
                'carnet' => 'AS251106',
                'password' => Hash::make('uls1234'),
                'role_id' => $clienteRole->id,
            ],
        );

        // 3. Catálogo de Servicios Médicos Primarios ULS
        $generalConsultation = Service::firstOrCreate(
            ['nombre' => 'Consulta Médica General'],
            [
                'descripcion' => 'Atención primaria, evaluación preventiva y seguimiento de padecimientos.',
                'activo' => true,
            ],
        );
        $firstAid = Service::firstOrCreate(
            ['nombre' => 'Atención de Primeros Auxilios'],
            [
                'descripcion' => 'Curaciones, curación de heridas menores y atención rápida ante emergencias en el campus.',
                'activo' => true,
            ],
        );
        $mentalHealth = Service::firstOrCreate(
            ['nombre' => 'Orientación de Salud Mental'],
            [
                'descripcion' => 'Acompañamiento psicológico primario y control del estrés académico.',
                'activo' => true,
            ],
        );

        $generalConsultation->doctors()->sync([$firstDoctor->id]);
        $firstAid->doctors()->sync([$secondDoctor->id]);
        $mentalHealth->doctors()->sync([$thirdDoctor->id]);
    }
}
