<?php

use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

function doctorManagementUser(string $roleName, array $attributes = []): User
{
    $role = Role::firstOrCreate(
        ['nombre' => $roleName],
        ['descripcion' => ucfirst($roleName)],
    );

    return User::factory()->create(array_merge([
        'role_id' => $role->id,
    ], $attributes));
}

function doctorManagementService(string $name, bool $isActive = true): Service
{
    return Service::create([
        'nombre' => $name,
        'descripcion' => 'Atención médica de prueba.',
        'activo' => $isActive,
    ]);
}

it('lets administrators register doctors directly with a free specialization', function () {
    $admin = doctorManagementUser('admin');
    $service = doctorManagementService('Consulta general');
    $assignedService = doctorManagementService('Primeros auxilios');
    $existingDoctor = doctorManagementUser('doctor', ['name' => 'Doctor Existente']);
    $patient = doctorManagementUser('cliente', ['name' => 'Paciente']);
    $assignedService->doctors()->attach($existingDoctor);

    $this->actingAs($admin)
        ->get(route('doctors.index'))
        ->assertOk()
        ->assertSee('Registrar nuevo doctor')
        ->assertSee('Doctor Existente')
        ->assertSee($assignedService->nombre)
        ->assertDontSee($service->nombre)
        ->assertDontSee('Paciente')
        ->assertSee('href="'.route('doctors.create').'"', false)
        ->assertDontSee('name="password"', false);

    $this->get(route('doctors.create'))
        ->assertOk()
        ->assertSee('Contraseña')
        ->assertSee($service->nombre);

    $this->post(route('doctors.store'), [
        'name' => 'Dra. Nueva',
        'email' => 'doctor.nueva@example.test',
        'carnet' => 'DOC-NEW',
        'service_id' => $service->id,
        'password' => 'StrongPass123!',
        'password_confirmation' => 'StrongPass123!',
    ])->assertRedirect(route('doctors.index'));

    $doctor = User::where('email', 'doctor.nueva@example.test')->firstOrFail();

    expect($doctor->isDoctor())->toBeTrue();
    expect(Hash::check('StrongPass123!', $doctor->password))->toBeTrue();

    $this->assertDatabaseHas('doctor_service', [
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
    ]);
    $this->delete('/doctors/'.$existingDoctor->id)
        ->assertStatus(405);
});

it('rejects doctor registration when the specialization is inactive or assigned to another doctor', function () {
    $admin = doctorManagementUser('admin');
    $inactiveService = doctorManagementService('Especialización inactiva', false);
    $assignedService = doctorManagementService('Especialización ocupada');
    $existingDoctor = doctorManagementUser('doctor');
    $assignedService->doctors()->attach($existingDoctor);

    $doctorData = [
        'name' => 'Doctor Nuevo',
        'email' => 'doctor.rejected@example.test',
        'carnet' => 'DOC-REJECTED',
        'password' => 'StrongPass123!',
        'password_confirmation' => 'StrongPass123!',
    ];

    $this->actingAs($admin)
        ->post(route('doctors.store'), $doctorData + ['service_id' => $inactiveService->id])
        ->assertSessionHasErrors('service_id');

    $this->post(route('doctors.store'), $doctorData + ['service_id' => $assignedService->id])
        ->assertSessionHasErrors('service_id');

    $this->assertDatabaseMissing('users', ['email' => 'doctor.rejected@example.test']);
});

it('keeps the no-specialization alert on the separate doctor form', function () {
    $admin = doctorManagementUser('admin');
    $assignedService = doctorManagementService('Especialización ocupada');
    $existingDoctor = doctorManagementUser('doctor');
    $assignedService->doctors()->attach($existingDoctor);

    $this->actingAs($admin)
        ->get(route('doctors.create'))
        ->assertOk()
        ->assertSee('No hay especializaciones activas disponibles sin doctor asignado. Crea una en Servicios o libera una existente antes de continuar.');
});

it('lets administrators update doctor details and specialization while keeping a blank password unchanged', function () {
    $admin = doctorManagementUser('admin');
    $doctor = doctorManagementUser('doctor', [
        'name' => 'Doctor Original',
        'email' => 'doctor.original@example.test',
        'carnet' => 'DOC-ORIGINAL',
    ]);
    $oldService = doctorManagementService('Especialización anterior');
    $newService = doctorManagementService('Especialización nueva');
    $doctor->services()->attach($oldService);

    $this->actingAs($admin)
        ->get(route('doctors.edit', $doctor))
        ->assertOk()
        ->assertSee('Editar doctor')
        ->assertSee($oldService->nombre)
        ->assertSee($newService->nombre)
        ->assertDontSee('Doctores registrados');

    $this->patch(route('doctors.update', $doctor), [
        'name' => 'Doctor Actualizado',
        'email' => 'doctor.actualizado@example.test',
        'carnet' => 'DOC-ACTUALIZADO',
        'service_id' => $newService->id,
        'password' => '',
        'password_confirmation' => '',
    ])->assertRedirect(route('doctors.index'));

    $this->assertDatabaseHas('users', [
        'id' => $doctor->id,
        'name' => 'Doctor Actualizado',
        'email' => 'doctor.actualizado@example.test',
        'carnet' => 'DOC-ACTUALIZADO',
    ]);
    $this->assertDatabaseMissing('doctor_service', [
        'doctor_id' => $doctor->id,
        'service_id' => $oldService->id,
    ]);
    $this->assertDatabaseHas('doctor_service', [
        'doctor_id' => $doctor->id,
        'service_id' => $newService->id,
    ]);
    expect(Hash::check('password', $doctor->fresh()->password))->toBeTrue();
});

it('lets administrators change a doctor password when they enter a new one', function () {
    $admin = doctorManagementUser('admin');
    $doctor = doctorManagementUser('doctor', ['carnet' => 'DOC-PASSWORD']);
    $service = doctorManagementService('Especialización actual');
    $doctor->services()->attach($service);

    $this->actingAs($admin)
        ->patch(route('doctors.update', $doctor), [
            'name' => $doctor->name,
            'email' => $doctor->email,
            'carnet' => $doctor->carnet,
            'service_id' => $service->id,
            'password' => 'NewDoctorPass123!',
            'password_confirmation' => 'NewDoctorPass123!',
        ])
        ->assertRedirect(route('doctors.index'));

    expect(Hash::check('NewDoctorPass123!', $doctor->fresh()->password))->toBeTrue();
});

it('rejects assigning a doctor to a specialization owned by another doctor', function () {
    $admin = doctorManagementUser('admin');
    $doctor = doctorManagementUser('doctor');
    $otherDoctor = doctorManagementUser('doctor');
    $currentService = doctorManagementService('Especialización actual');
    $occupiedService = doctorManagementService('Especialización de otro doctor');
    $doctor->services()->attach($currentService);
    $occupiedService->doctors()->attach($otherDoctor);

    $this->actingAs($admin)
        ->patch(route('doctors.update', $doctor), [
            'name' => $doctor->name,
            'email' => $doctor->email,
            'carnet' => 'DOC-UPDATED',
            'service_id' => $occupiedService->id,
            'password' => '',
            'password_confirmation' => '',
        ])
        ->assertSessionHasErrors('service_id');

    $this->assertDatabaseHas('doctor_service', [
        'doctor_id' => $doctor->id,
        'service_id' => $currentService->id,
    ]);
});

it('forbids non-administrators from viewing or changing doctor accounts', function (string $roleName) {
    $user = doctorManagementUser($roleName);
    $doctor = doctorManagementUser('doctor');

    $this->actingAs($user)
        ->get(route('doctors.index'))
        ->assertForbidden();

    $this->get(route('doctors.edit', $doctor))->assertForbidden();
    $this->get(route('doctors.create'))->assertForbidden();
    $this->post(route('doctors.store'), [])->assertForbidden();
    $this->patch(route('doctors.update', $doctor), [])->assertForbidden();
})->with([
    'doctor' => 'doctor',
    'patient' => 'cliente',
]);

it('does not let administrators edit accounts that are not doctors', function () {
    $admin = doctorManagementUser('admin');
    $patient = doctorManagementUser('cliente');

    $this->actingAs($admin)
        ->get(route('doctors.edit', $patient))
        ->assertNotFound();

    $this->patch(route('doctors.update', $patient), [])
        ->assertNotFound();
});

it('does not let doctor registration change roles through the user list', function () {
    $admin = doctorManagementUser('admin');
    $patient = doctorManagementUser('cliente', ['name' => 'Paciente']);

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertOk()
        ->assertSee('Paciente')
        ->assertDontSee('Cambiar Rol')
        ->assertDontSee('name="role_id"', false);

    $this->patch('/users/'.$patient->id.'/role', ['role_id' => Role::where('nombre', 'doctor')->value('id')])
        ->assertNotFound();

    expect($patient->fresh()->isCliente())->toBeTrue();

});

it('allows existing doctor accounts to log in', function () {
    $doctor = doctorManagementUser('doctor');

    $this->post(route('login'), [
        'login' => $doctor->email,
        'password' => 'password',
    ])->assertRedirect(route('home', absolute: false));

    $this->assertAuthenticatedAs($doctor);
});
