<?php

use App\Models\Appointment;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;

function dashboardTestUser(string $roleName): User
{
    $role = Role::where('nombre', $roleName)->firstOrFail();

    return User::factory()->create([
        'role_id' => $role->id,
    ]);
}

it('shows administrators system counts without appointment management details', function () {
    $admin = dashboardTestUser('admin');
    $doctor = dashboardTestUser('doctor');
    $patient = dashboardTestUser('cliente');
    $secondPatient = dashboardTestUser('cliente');
    $service = Service::create([
        'nombre' => 'Consulta general',
        'descripcion' => 'Atención médica general.',
        'activo' => true,
    ]);

    Appointment::create([
        'patient_id' => $patient->id,
        'doctor_id' => null,
        'service_id' => $service->id,
        'fecha_hora' => today()->startOfDay(),
        'estado' => 'pendiente',
    ]);

    Appointment::create([
        'patient_id' => $secondPatient->id,
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'fecha_hora' => now()->addDay(),
        'estado' => 'confirmada',
    ]);

    $this->actingAs($admin)
        ->get(route('home'))
        ->assertOk()
        ->assertViewIs('dashboard')
        ->assertViewHas('totalAppointmentCount', 2)
        ->assertViewHas('todayAppointmentCount', 1)
        ->assertViewHas('pendingAppointmentCount', 1)
        ->assertViewHas('unassignedAppointmentCount', 1)
        ->assertViewHas('confirmedAppointmentCount', 1)
        ->assertViewHas('doctorCount', 1)
        ->assertViewHas('patientCount', 2)
        ->assertViewHas('activeServiceCount', 1)
        ->assertSee('Estado del sistema')
        ->assertSee('Citas registradas')
        ->assertDontSee('Próximas citas')
        ->assertDontSee($secondPatient->name)
        ->assertDontSee($doctor->name);
});

it('shows patients an account summary with only their own appointments', function () {
    $patient = dashboardTestUser('cliente');
    $otherPatient = dashboardTestUser('cliente');
    $doctor = dashboardTestUser('doctor');
    $service = Service::create([
        'nombre' => 'Consulta de prueba',
        'descripcion' => 'Servicio para probar el resumen.',
        'activo' => true,
    ]);

    Appointment::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'fecha_hora' => now()->addDay(),
        'estado' => 'pendiente',
        'motivo' => 'Dolor de cabeza propio',
    ]);

    Appointment::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'fecha_hora' => now()->subDay(),
        'estado' => 'completada',
        'motivo' => 'Consulta anterior propia',
    ]);

    Appointment::create([
        'patient_id' => $otherPatient->id,
        'service_id' => $service->id,
        'fecha_hora' => now()->addDays(2),
        'estado' => 'pendiente',
        'motivo' => 'Motivo privado de otra persona',
    ]);

    $this->actingAs($patient)
        ->get(route('home'))
        ->assertOk()
        ->assertViewIs('patient.dashboard')
        ->assertViewHas('totalAppointmentCount', 2)
        ->assertViewHas('pendingAppointmentCount', 1)
        ->assertViewHas('completedAppointmentCount', 1)
        ->assertViewHas('upcomingAppointments', fn ($appointments) => $appointments->count() === 1)
        ->assertSee('Resumen de mi cuenta')
        ->assertSee('Dolor de cabeza propio')
        ->assertDontSee('Motivo privado de otra persona');
});

it('shows administrators the system summary on the root page without an Inicio tab', function () {
    $admin = dashboardTestUser('admin');

    $this->actingAs($admin)
        ->get(route('home'))
        ->assertOk()
        ->assertViewIs('dashboard')
        ->assertSee('Estado del sistema')
        ->assertDontSee('Inicio')
        ->assertSee('Citas registradas')
        ->assertDontSee('Servicios Médicos Disponibles');
});

it('shows the public service catalog only to visitors', function () {
    $service = Service::create([
        'nombre' => 'Servicio visible a visitantes',
        'descripcion' => 'Descripción del servicio.',
        'activo' => true,
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Servicios Médicos Disponibles')
        ->assertSee($service->nombre);
});

it('shows patients their account summary from the root page', function () {
    $patient = dashboardTestUser('cliente');

    $this->actingAs($patient)
        ->get(route('home'))
        ->assertOk()
        ->assertViewIs('patient.dashboard')
        ->assertSee('Resumen de mi cuenta');
});

it('redirects doctors from the public home to their appointments', function () {
    $doctor = dashboardTestUser('doctor');

    $this->actingAs($doctor)
        ->get(route('home'))
        ->assertRedirect(route('appointments.index'));
});

it('does not expose the removed dashboard URL', function () {
    $this->get('/dashboard')->assertNotFound();
});

it('shows administrators management links without an Inicio tab', function () {
    $admin = dashboardTestUser('admin');
    $response = $this->actingAs($admin)->get(route('home'));

    $response->assertOk()
        ->assertDontSee('Inicio')
        ->assertSee('href="'.route('home').'" class="text-2xl font-black tracking-wider flex items-center gap-2"', false)
        ->assertSee('Cerrar sesión');

    $orderedLinks = [
        'href="'.route('appointments.index').'"',
        'href="'.route('services.index').'"',
        'href="'.route('doctors.index').'"',
        'href="'.route('users.index').'"',
        'action="'.route('logout').'"',
    ];
    $previousPosition = -1;
    $content = $response->getContent();

    foreach ($orderedLinks as $link) {
        $position = strpos($content, $link);

        expect($position !== false && $position > $previousPosition)->toBeTrue();
        $previousPosition = $position;
    }
});

it('shows patients their own appointments label without administrative links', function () {
    $patient = dashboardTestUser('cliente');

    $this->actingAs($patient)
        ->get(route('appointments.index'))
        ->assertOk()
        ->assertSee('Mis citas')
        ->assertDontSee('href="'.route('services.index').'"', false)
        ->assertDontSee('href="'.route('doctors.index').'"', false)
        ->assertDontSee('href="'.route('users.index').'"', false);
});

it('shows doctors the medical appointments label without administrative links', function () {
    $doctor = dashboardTestUser('doctor');

    $this->actingAs($doctor)
        ->get(route('appointments.index'))
        ->assertOk()
        ->assertSee('Citas médicas')
        ->assertDontSee('href="'.route('services.index').'"', false)
        ->assertDontSee('href="'.route('doctors.index').'"', false)
        ->assertDontSee('href="'.route('users.index').'"', false);
});
