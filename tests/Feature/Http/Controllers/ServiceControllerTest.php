<?php

use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\QueryException;

function serviceUserWithRole(string $roleName): User
{
    $role = Role::firstOrCreate(
        ['nombre' => $roleName],
        ['descripcion' => ucfirst($roleName)],
    );

    return User::factory()->create([
        'role_id' => $role->id,
    ]);
}

it('hides the services link from doctors', function () {
    $doctor = serviceUserWithRole('doctor');

    $this->actingAs($doctor)
        ->get(route('appointments.index'))
        ->assertOk()
        ->assertDontSee('href="'.route('services.index').'"', false);
});

it('forbids doctors from viewing the service catalog', function () {
    $doctor = serviceUserWithRole('doctor');

    $this->actingAs($doctor)
        ->get(route('services.index'))
        ->assertForbidden();
});

it('forbids doctors from creating services', function () {
    $doctor = serviceUserWithRole('doctor');

    $this->actingAs($doctor)
        ->post(route('services.store'), [
            'nombre' => 'Servicio no autorizado',
            'descripcion' => 'No debe agregarse.',
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('services', [
        'nombre' => 'Servicio no autorizado',
    ]);
});

it('forbids doctors from changing service availability', function () {
    $doctor = serviceUserWithRole('doctor');
    $service = Service::create([
        'nombre' => 'Consulta general',
        'descripcion' => 'Atención médica general.',
        'activo' => true,
    ]);

    $this->actingAs($doctor)
        ->patch(route('services.toggle', $service))
        ->assertForbidden();

    $this->assertDatabaseHas('services', [
        'id' => $service->id,
        'activo' => true,
    ]);
});

it('shows the services link to administrators', function () {
    $admin = serviceUserWithRole('admin');

    $this->actingAs($admin)
        ->get(route('appointments.index'))
        ->assertOk()
        ->assertSee('href="'.route('services.index').'"', false);
});

it('allows administrators to create and deactivate services', function () {
    $admin = serviceUserWithRole('admin');

    $this->actingAs($admin)
        ->get(route('services.index'))
        ->assertOk();

    $this->actingAs($admin)
        ->post(route('services.store'), [
            'nombre' => 'Consulta general',
            'descripcion' => 'Atención médica general.',
        ])
        ->assertRedirect();

    $service = Service::where('nombre', 'Consulta general')->firstOrFail();

    $this->assertDatabaseHas('services', [
        'id' => $service->id,
        'activo' => true,
    ]);

    $this->patch(route('services.toggle', $service))
        ->assertRedirect();

    $this->assertDatabaseHas('services', [
        'id' => $service->id,
        'activo' => false,
    ]);
});

it('shows the fixed doctor for a service without offering reassignment', function () {
    $admin = serviceUserWithRole('admin');
    $doctor = serviceUserWithRole('doctor');
    $service = Service::create([
        'nombre' => 'Consulta general',
        'descripcion' => 'Atención médica general.',
        'activo' => true,
    ]);
    $service->doctors()->attach($doctor);

    $this->actingAs($admin)
        ->get(route('services.index'))
        ->assertOk()
        ->assertSee($doctor->name)
        ->assertDontSee('Guardar asignación');

    $this->patch('/services/'.$service->id.'/doctors', ['doctor_ids' => [$doctor->id]])
        ->assertNotFound();

    $this->assertDatabaseHas('doctor_service', [
        'service_id' => $service->id,
        'doctor_id' => $doctor->id,
    ]);
});

it('does not expose a route for assigning a patient to a service as a doctor', function () {
    $patient = serviceUserWithRole('cliente');
    $service = Service::create([
        'nombre' => 'Consulta general',
        'descripcion' => 'Atención médica general.',
        'activo' => true,
    ]);

    $this->patch('/services/'.$service->id.'/doctors', [
        'doctor_ids' => [$patient->id],
    ])->assertNotFound();

    $this->assertDatabaseMissing('doctor_service', [
        'service_id' => $service->id,
        'doctor_id' => $patient->id,
    ]);
});

it('does not expose a service reassignment route to doctors', function () {
    $doctor = serviceUserWithRole('doctor');
    $service = Service::create([
        'nombre' => 'Consulta general',
        'descripcion' => 'Atención médica general.',
        'activo' => true,
    ]);

    $this->actingAs($doctor)
        ->patch('/services/'.$service->id.'/doctors', [
            'doctor_ids' => [$doctor->id],
        ])
        ->assertNotFound();

    $this->assertDatabaseMissing('doctor_service', [
        'service_id' => $service->id,
        'doctor_id' => $doctor->id,
    ]);
});

it('enforces one doctor per service in the database', function () {
    $service = Service::create([
        'nombre' => 'Consulta general',
        'descripcion' => 'Atención médica general.',
        'activo' => true,
    ]);
    $firstDoctor = serviceUserWithRole('doctor');
    $secondDoctor = serviceUserWithRole('doctor');

    $service->doctors()->attach($firstDoctor);

    expect(fn () => $service->doctors()->attach($secondDoctor))
        ->toThrow(QueryException::class);
});
