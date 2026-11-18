<?php

use App\Models\Role;
use App\Models\User;

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

it('lets administrators view doctors without being able to change user roles', function () {
    $admin = doctorManagementUser('admin');
    $existingDoctor = doctorManagementUser('doctor', ['name' => 'Doctor Existente']);
    $patient = doctorManagementUser('cliente', ['name' => 'Paciente']);

    $this->actingAs($admin)
        ->get(route('doctors.index'))
        ->assertOk()
        ->assertSee('Doctor Existente')
        ->assertDontSee('Paciente')
        ->assertDontSee('Agregar doctor');

    $this->get('/doctors/create')->assertNotFound();
    $this->post('/doctors', [])->assertStatus(405);

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertOk()
        ->assertSee('Paciente')
        ->assertSee('Cliente')
        ->assertDontSee('Cambiar Rol')
        ->assertDontSee('name="role_id"', false);

    $this->patch('/users/'.$patient->id.'/role', ['role_id' => $patient->role_id])
        ->assertNotFound();

    expect($patient->fresh()->isCliente())->toBeTrue();
    $this->assertModelExists($existingDoctor);
});

it('forbids non-administrators from managing doctor accounts', function (string $roleName) {
    $user = doctorManagementUser($roleName);

    $this->actingAs($user)
        ->get(route('doctors.index'))
        ->assertForbidden();

    $this->get('/doctors/create')->assertNotFound();
    $this->post('/doctors', [])->assertStatus(405);
})->with([
    'doctor' => 'doctor',
    'patient' => 'cliente',
]);

it('does not expose doctor account creation, editing, or deletion routes', function () {
    $admin = doctorManagementUser('admin');
    $doctor = doctorManagementUser('doctor');

    $this->actingAs($admin)
        ->get('/doctors/'.$doctor->id.'/edit')
        ->assertNotFound();

    $this->put('/doctors/'.$doctor->id, [])
        ->assertNotFound();

    $this->delete('/doctors/'.$doctor->id)
        ->assertNotFound();

    $this->assertModelExists($doctor);
});

it('allows existing doctor accounts to log in', function () {
    $doctor = doctorManagementUser('doctor');

    $this->post(route('login'), [
        'login' => $doctor->email,
        'password' => 'password',
    ])->assertRedirect(route('home', absolute: false));

    $this->assertAuthenticatedAs($doctor);
});
