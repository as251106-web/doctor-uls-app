<?php

use App\Models\Appointment;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Policies\AppointmentPolicy;

function appointmentTestUser(string $roleName, array $attributes = []): User
{
    $role = Role::where('nombre', $roleName)->firstOrFail();

    return User::factory()->create(array_merge([
        'role_id' => $role->id,
    ], $attributes));
}

function appointmentTestService(string $name = 'Consulta general'): Service
{
    return Service::create([
        'nombre' => $name,
        'descripcion' => 'Atención médica general.',
        'activo' => true,
    ]);
}

function appointmentTestStart(int $daysFromNow = 2, string $time = '10:00'): Carbon\Carbon
{
    [$hour, $minute] = array_map('intval', explode(':', $time));

    return now()->addDays($daysFromNow)->setTime($hour, $minute);
}

it('shows patients their consultation details in cards without exposing other patients', function () {
    $patient = appointmentTestUser('cliente');
    $otherPatient = appointmentTestUser('cliente');
    $doctor = appointmentTestUser('doctor');
    $service = appointmentTestService();
    $startTime = appointmentTestStart();

    Appointment::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'fecha_hora' => $startTime,
        'estado' => 'pendiente',
        'motivo' => 'Dolor de garganta propio',
    ]);

    Appointment::create([
        'patient_id' => $otherPatient->id,
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'fecha_hora' => appointmentTestStart(3),
        'estado' => 'confirmada',
        'motivo' => 'Síntoma privado de otra persona',
    ]);

    $this->actingAs($patient)
        ->get(route('appointments.index'))
        ->assertOk()
        ->assertSee($service->nombre)
        ->assertSee($startTime->format('d/m/Y - h:i A'))
        ->assertSee('Dolor de garganta propio')
        ->assertSee('Pendiente')
        ->assertDontSee('Reagendar')
        ->assertDontSee('Síntoma privado de otra persona');
});

it('shows doctors their assigned consultation details in cards without other doctors appointments', function () {
    $doctor = appointmentTestUser('doctor');
    $otherDoctor = appointmentTestUser('doctor');
    $patient = appointmentTestUser('cliente');
    $service = appointmentTestService();
    $startTime = appointmentTestStart();

    Appointment::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'fecha_hora' => $startTime,
        'estado' => 'confirmada',
        'motivo' => 'Dolor de espalda asignado',
    ]);

    Appointment::create([
        'patient_id' => appointmentTestUser('cliente')->id,
        'doctor_id' => $otherDoctor->id,
        'service_id' => $service->id,
        'fecha_hora' => appointmentTestStart(3),
        'estado' => 'confirmada',
        'motivo' => 'Consulta privada de otro doctor',
    ]);

    $this->actingAs($doctor)
        ->get(route('appointments.index'))
        ->assertOk()
        ->assertSee($service->nombre)
        ->assertSee($startTime->format('d/m/Y - h:i A'))
        ->assertSee('Dolor de espalda asignado')
        ->assertSee('Confirmada')
        ->assertDontSee('Solicitudes disponibles')
        ->assertDontSee('No hay solicitudes disponibles para tus servicios')
        ->assertSee('Reagendar')
        ->assertDontSee('Consulta privada de otro doctor');
});

it('automatically assigns a service doctor when no consultation is active', function () {
    $doctor = appointmentTestUser('doctor');
    $patient = appointmentTestUser('cliente');
    $service = appointmentTestService();
    $service->doctors()->attach($doctor);
    $startTime = appointmentTestStart();

    $this->actingAs($patient)
        ->post(route('appointments.store'), [
            'service_id' => $service->id,
            'fecha_hora' => $startTime->format('Y-m-d\TH:i'),
            'motivo' => 'Consulta de prueba',
        ])
        ->assertRedirect(route('appointments.index'));

    $this->assertDatabaseHas('appointments', [
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'fecha_hora' => $startTime->format('Y-m-d H:i:s'),
        'estado' => 'pendiente',
    ]);
});

it('rejects bookings for services without an assigned doctor', function () {
    $patient = appointmentTestUser('cliente');
    $service = appointmentTestService();

    $this->actingAs($patient)
        ->post(route('appointments.store'), [
            'service_id' => $service->id,
            'fecha_hora' => appointmentTestStart()->format('Y-m-d\TH:i'),
        ])
        ->assertSessionHasErrors('service_id');

    $this->assertDatabaseCount('appointments', 0);
});

it('blocks doctors and administrators from opening or submitting booking requests', function (string $roleName) {
    $user = appointmentTestUser($roleName);
    $service = appointmentTestService();

    $this->actingAs($user)
        ->get(route('appointments.create'))
        ->assertForbidden();

    $this->post(route('appointments.store'), [
        'service_id' => $service->id,
        'fecha_hora' => appointmentTestStart()->format('Y-m-d\TH:i'),
    ])->assertForbidden();

    $this->assertDatabaseCount('appointments', 0);
})->with([
    'doctor' => 'doctor',
    'administrator' => 'admin',
]);

it('does not show booking controls to administrators', function () {
    $admin = appointmentTestUser('admin');

    $this->actingAs($admin)
        ->get(route('home'))
        ->assertOk()
        ->assertDontSee('Agendar cita')
        ->assertDontSee('Agendar Cita Médica');

    $this->get(route('appointments.index'))
        ->assertOk()
        ->assertDontSee('Agendar nueva cita');
});

it('assigns the service doctor even when they have an active consultation', function () {
    $doctor = appointmentTestUser('doctor');
    $patient = appointmentTestUser('cliente');
    $service = appointmentTestService();
    $service->doctors()->attach($doctor);
    $existingStart = appointmentTestStart();
    $requestedStart = $existingStart->copy()->addHours(4);

    Appointment::create([
        'patient_id' => appointmentTestUser('cliente')->id,
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'fecha_hora' => $existingStart,
        'estado' => 'confirmada',
    ]);

    $this->actingAs($patient)
        ->post(route('appointments.store'), [
            'service_id' => $service->id,
            'fecha_hora' => $requestedStart->format('Y-m-d\TH:i'),
        ])
        ->assertRedirect(route('appointments.index'));

    $this->assertDatabaseHas('appointments', [
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'fecha_hora' => $requestedStart->format('Y-m-d H:i:s'),
        'estado' => 'pendiente',
    ]);
});

it('keeps appointments assigned to the service doctor without a separate request queue', function () {
    $serviceDoctor = appointmentTestUser('doctor');
    $unrelatedDoctor = appointmentTestUser('doctor');
    $admin = appointmentTestUser('admin');
    $service = appointmentTestService();
    $service->doctors()->attach($serviceDoctor);
    $startTime = appointmentTestStart();

    Appointment::create([
        'patient_id' => appointmentTestUser('cliente', ['name' => 'Paciente del doctor actual'])->id,
        'doctor_id' => $serviceDoctor->id,
        'service_id' => $service->id,
        'fecha_hora' => $startTime,
        'estado' => 'confirmada',
    ]);

    $waitingPatient = appointmentTestUser('cliente', ['name' => 'Paciente en espera']);
    $this->actingAs($waitingPatient)
        ->post(route('appointments.store'), [
            'service_id' => $service->id,
            'fecha_hora' => $startTime->format('Y-m-d\TH:i'),
        ])
        ->assertRedirect(route('appointments.index'));

    $this->assertDatabaseHas('appointments', [
        'patient_id' => $waitingPatient->id,
        'doctor_id' => $serviceDoctor->id,
        'service_id' => $service->id,
        'estado' => 'pendiente',
    ]);

    $this->actingAs($serviceDoctor)
        ->get(route('appointments.index'))
        ->assertOk()
        ->assertSee('Paciente del doctor actual')
        ->assertSee('Paciente en espera')
        ->assertDontSee('Solicitudes disponibles')
        ->assertDontSee('No hay solicitudes disponibles para tus servicios');

    $this->actingAs($unrelatedDoctor)
        ->get(route('appointments.index'))
        ->assertOk()
        ->assertDontSee('Paciente en espera');

    $this->actingAs($admin)
        ->get(route('appointments.index'))
        ->assertOk()
        ->assertSee('Paciente del doctor actual')
        ->assertSee('Paciente en espera');
});

it('lets administrators open completed consultation details from the appointment list', function () {
    $admin = appointmentTestUser('admin');
    $patient = appointmentTestUser('cliente');
    $doctor = appointmentTestUser('doctor');
    $service = appointmentTestService();
    $appointment = Appointment::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'fecha_hora' => appointmentTestStart(),
        'estado' => 'completada',
        'motivo' => 'Consulta completada para el administrador',
    ]);

    $this->actingAs($admin)
        ->get(route('appointments.index'))
        ->assertOk()
        ->assertSee('Ver detalles')
        ->assertSee(route('medical-records.show', $appointment));
});

it('shows service doctor availability on the appointment form', function () {
    $patient = appointmentTestUser('cliente');
    $doctor = appointmentTestUser('doctor');
    $service = appointmentTestService();
    $service->doctors()->attach($doctor);

    $this->actingAs($patient)
        ->get(route('appointments.create'))
        ->assertOk()
        ->assertSee('data-doctor-available="true"', false)
        ->assertSee('está disponible para atender una consulta');

    Appointment::create([
        'patient_id' => appointmentTestUser('cliente')->id,
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'fecha_hora' => appointmentTestStart(),
        'estado' => 'pendiente',
    ]);

    $this->get(route('appointments.create'))
        ->assertOk()
        ->assertSee('data-doctor-available="false"', false)
        ->assertSee('no está disponible porque tiene una consulta pendiente o en curso');
});

it('lets a service doctor take and reschedule an unassigned request', function () {
    $doctor = appointmentTestUser('doctor');
    $patient = appointmentTestUser('cliente');
    $service = appointmentTestService();
    $service->doctors()->attach($doctor);
    $requestedStart = appointmentTestStart();
    $newStart = appointmentTestStart(3, '14:00');
    $appointment = Appointment::create([
        'patient_id' => $patient->id,
        'service_id' => $service->id,
        'fecha_hora' => $requestedStart,
        'estado' => 'pendiente',
    ]);

    $this->actingAs($doctor)
        ->post(route('appointments.claim', $appointment), [
            'fecha_hora' => $newStart->format('Y-m-d\TH:i'),
        ])
        ->assertRedirect(route('appointments.index'));

    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'doctor_id' => $doctor->id,
        'fecha_hora' => $newStart->format('Y-m-d H:i:s'),
        'estado' => 'confirmada',
    ]);
});

it('lets the assigned doctor reschedule a pending or confirmed patient appointment', function (string $status) {
    $doctor = appointmentTestUser('doctor');
    $patient = appointmentTestUser('cliente');
    $service = appointmentTestService();
    $originalStart = appointmentTestStart();
    $newStart = appointmentTestStart(5, '15:30');
    $appointment = Appointment::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'fecha_hora' => $originalStart,
        'estado' => $status,
    ]);

    $this->actingAs($doctor)
        ->patch(route('appointments.reschedule', $appointment), [
            'fecha_hora' => $newStart->format('Y-m-d\TH:i'),
        ])
        ->assertRedirect(route('appointments.index'));

    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'fecha_hora' => $newStart->format('Y-m-d H:i:s'),
        'estado' => $status,
    ]);
})->with([
    'pending' => 'pendiente',
    'confirmed' => 'confirmada',
]);

it('forbids patients, administrators, and other doctors from rescheduling an appointment', function (string $roleName) {
    $doctor = appointmentTestUser('doctor');
    $patient = appointmentTestUser('cliente');
    $otherDoctor = appointmentTestUser('doctor');
    $admin = appointmentTestUser('admin');
    $service = appointmentTestService();
    $originalStart = appointmentTestStart();
    $appointment = Appointment::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'fecha_hora' => $originalStart,
        'estado' => 'confirmada',
    ]);
    $unauthorizedUser = match ($roleName) {
        'cliente' => $patient,
        'doctor' => $otherDoctor,
        default => $admin,
    };

    $this->actingAs($unauthorizedUser)
        ->patch(route('appointments.reschedule', $appointment), [
            'fecha_hora' => appointmentTestStart(5)->format('Y-m-d\TH:i'),
        ])
        ->assertForbidden();

    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'doctor_id' => $doctor->id,
        'fecha_hora' => $originalStart->format('Y-m-d H:i:s'),
        'estado' => 'confirmada',
    ]);
})->with([
    'patient' => 'cliente',
    'other doctor' => 'doctor',
    'administrator' => 'admin',
]);

it('does not let a doctor reschedule a completed or cancelled appointment', function (string $status) {
    $doctor = appointmentTestUser('doctor');
    $patient = appointmentTestUser('cliente');
    $service = appointmentTestService();
    $originalStart = appointmentTestStart();
    $appointment = Appointment::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'fecha_hora' => $originalStart,
        'estado' => $status,
    ]);

    $this->actingAs($doctor)
        ->patch(route('appointments.reschedule', $appointment), [
            'fecha_hora' => appointmentTestStart(5)->format('Y-m-d\TH:i'),
        ])
        ->assertForbidden();

    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'fecha_hora' => $originalStart->format('Y-m-d H:i:s'),
        'estado' => $status,
    ]);

    $this->get(route('appointments.index'))
        ->assertOk()
        ->assertDontSee('Reagendar');
})->with([
    'completed' => 'completada',
    'cancelled' => 'cancelada',
]);

it('rejects a past date when a doctor reschedules an appointment', function () {
    $doctor = appointmentTestUser('doctor');
    $patient = appointmentTestUser('cliente');
    $service = appointmentTestService();
    $originalStart = appointmentTestStart();
    $appointment = Appointment::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'fecha_hora' => $originalStart,
        'estado' => 'confirmada',
    ]);

    $this->actingAs($doctor)
        ->patch(route('appointments.reschedule', $appointment), [
            'fecha_hora' => now()->subMinute()->format('Y-m-d\TH:i'),
        ])
        ->assertSessionHasErrors('fecha_hora');

    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'fecha_hora' => $originalStart->format('Y-m-d H:i:s'),
        'estado' => 'confirmada',
    ]);
});

it('lets a service doctor take an unassigned request for the next available minute', function () {
    $doctor = appointmentTestUser('doctor');
    $patient = appointmentTestUser('cliente');
    $service = appointmentTestService();
    $service->doctors()->attach($doctor);
    $appointment = Appointment::create([
        'patient_id' => $patient->id,
        'service_id' => $service->id,
        'fecha_hora' => appointmentTestStart(),
        'estado' => 'pendiente',
    ]);
    $minimumStart = now();

    $this->actingAs($doctor)
        ->post(route('appointments.claim', $appointment), ['take_now' => 1])
        ->assertRedirect(route('appointments.index'));

    $appointment->refresh();

    expect($appointment->doctor_id)->toBe($doctor->id)
        ->and($appointment->estado)->toBe('confirmada')
        ->and(Carbon\Carbon::parse($appointment->fecha_hora)->greaterThanOrEqualTo($minimumStart))->toBeTrue();
});

it('prevents a doctor from taking a request while another consultation is active', function () {
    $doctor = appointmentTestUser('doctor');
    $patient = appointmentTestUser('cliente');
    $service = appointmentTestService();
    $service->doctors()->attach($doctor);
    $appointment = Appointment::create([
        'patient_id' => $patient->id,
        'service_id' => $service->id,
        'fecha_hora' => appointmentTestStart(),
        'estado' => 'pendiente',
    ]);
    Appointment::create([
        'patient_id' => appointmentTestUser('cliente')->id,
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'fecha_hora' => appointmentTestStart(10),
        'estado' => 'confirmada',
    ]);

    $this->actingAs($doctor)
        ->post(route('appointments.claim', $appointment))
        ->assertSessionHasErrors('fecha_hora');

    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'doctor_id' => null,
        'estado' => 'pendiente',
    ]);
});

it('prevents a doctor from taking a request for a service they do not provide', function () {
    $doctor = appointmentTestUser('doctor');
    $patient = appointmentTestUser('cliente');
    $service = appointmentTestService();
    $appointment = Appointment::create([
        'patient_id' => $patient->id,
        'service_id' => $service->id,
        'fecha_hora' => appointmentTestStart(),
        'estado' => 'pendiente',
    ]);

    $this->actingAs($doctor)
        ->post(route('appointments.claim', $appointment))
        ->assertForbidden();

    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'doctor_id' => null,
    ]);
});

it('releases a doctor after a patient cancels an appointment', function () {
    $doctor = appointmentTestUser('doctor');
    $patient = appointmentTestUser('cliente');
    $nextPatient = appointmentTestUser('cliente');
    $service = appointmentTestService();
    $service->doctors()->attach($doctor);
    $startTime = appointmentTestStart();
    $cancelledAppointment = Appointment::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'fecha_hora' => $startTime,
        'estado' => 'confirmada',
    ]);

    $this->actingAs($patient)
        ->patch(route('appointments.cancel', $cancelledAppointment))
        ->assertRedirect(route('appointments.index'));

    $this->actingAs($nextPatient)
        ->post(route('appointments.store'), [
            'service_id' => $service->id,
            'fecha_hora' => $startTime->format('Y-m-d\TH:i'),
        ])
        ->assertRedirect(route('appointments.index'));

    $this->assertDatabaseHas('appointments', [
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'fecha_hora' => $startTime->format('Y-m-d H:i:s'),
        'estado' => 'pendiente',
    ]);

    $this->assertDatabaseHas('appointments', [
        'id' => $cancelledAppointment->id,
        'estado' => 'cancelada',
    ]);
});

it('releases the doctor when they mark their appointment completed', function () {
    $doctor = appointmentTestUser('doctor');
    $patient = appointmentTestUser('cliente');
    $nextPatient = appointmentTestUser('cliente');
    $service = appointmentTestService();
    $service->doctors()->attach($doctor);
    $startTime = appointmentTestStart();
    $appointment = Appointment::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'fecha_hora' => $startTime,
        'estado' => 'confirmada',
    ]);

    $this->actingAs($doctor)
        ->patch(route('appointments.updateStatus', $appointment), [
            'estado' => 'completada',
        ])
        ->assertRedirect(route('appointments.index'));

    $this->actingAs($nextPatient)
        ->post(route('appointments.store'), [
            'service_id' => $service->id,
            'fecha_hora' => $startTime->format('Y-m-d\TH:i'),
        ])
        ->assertRedirect(route('appointments.index'));

    $this->assertDatabaseHas('appointments', [
        'patient_id' => $nextPatient->id,
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'fecha_hora' => $startTime->format('Y-m-d H:i:s'),
    ]);
});

it('prevents a doctor from changing another doctor appointment', function () {
    $doctor = appointmentTestUser('doctor');
    $otherDoctor = appointmentTestUser('doctor');
    $patient = appointmentTestUser('cliente');
    $service = appointmentTestService();
    $service->doctors()->attach($doctor);
    $appointment = Appointment::create([
        'patient_id' => $patient->id,
        'doctor_id' => $otherDoctor->id,
        'service_id' => $service->id,
        'fecha_hora' => appointmentTestStart(),
        'estado' => 'pendiente',
    ]);

    $this->actingAs($doctor)
        ->patch(route('appointments.updateStatus', $appointment), ['estado' => 'completada'])
        ->assertForbidden();

    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'estado' => 'pendiente',
        'doctor_id' => $otherDoctor->id,
    ]);
});

it('defines who can create and reschedule appointments in the policy', function () {
    $patient = appointmentTestUser('cliente');
    $doctor = appointmentTestUser('doctor');
    $otherDoctor = appointmentTestUser('doctor');
    $admin = appointmentTestUser('admin');
    $service = appointmentTestService();
    $activeAppointment = Appointment::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'fecha_hora' => appointmentTestStart(),
        'estado' => 'confirmada',
    ]);
    $completedAppointment = Appointment::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'fecha_hora' => appointmentTestStart(3),
        'estado' => 'completada',
    ]);
    $policy = new AppointmentPolicy;

    expect($policy->create($patient))->toBeTrue()
        ->and($policy->create($doctor))->toBeFalse()
        ->and($policy->create($admin))->toBeFalse()
        ->and($policy->reschedule($doctor, $activeAppointment))->toBeTrue()
        ->and($policy->reschedule($otherDoctor, $activeAppointment))->toBeFalse()
        ->and($policy->reschedule($patient, $activeAppointment))->toBeFalse()
        ->and($policy->reschedule($admin, $activeAppointment))->toBeFalse()
        ->and($policy->reschedule($doctor, $completedAppointment))->toBeFalse();
});
