<?php

use App\Models\Appointment;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Policies\AppointmentPolicy;
use Carbon\Carbon;

function medicalRecordTestUser(string $roleName, array $attributes = []): User
{
    $role = Role::where('nombre', $roleName)->firstOrFail();

    return User::factory()->create(array_merge([
        'role_id' => $role->id,
    ], $attributes));
}

function medicalRecordTestAppointment(
    User $patient,
    ?User $doctor,
    string $status = 'completada',
    string $serviceName = 'Consulta de expediente',
): Appointment {
    $service = Service::create([
        'nombre' => $serviceName,
        'descripcion' => 'Consulta de prueba.',
        'activo' => true,
    ]);

    return Appointment::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor?->id,
        'service_id' => $service->id,
        'fecha_hora' => now()->subDay(),
        'estado' => $status,
        'motivo' => 'Consulta de seguimiento',
    ]);
}

it('allows the patient, assigned doctor, and administrator to view a completed medical record', function () {
    $patient = medicalRecordTestUser('cliente');
    $otherPatient = medicalRecordTestUser('cliente');
    $doctor = medicalRecordTestUser('doctor');
    $otherDoctor = medicalRecordTestUser('doctor');
    $admin = medicalRecordTestUser('admin');
    $appointment = medicalRecordTestAppointment($patient, $doctor);
    $appointment->update([
        'diagnostico' => 'Diagnóstico de prueba',
        'observaciones_medicas' => 'Notas para consulta',
    ]);

    $this->actingAs($patient)
        ->get(route('medical-records.show', $appointment))
        ->assertOk()
        ->assertSee('Consulta de seguimiento');

    $this->actingAs($doctor)
        ->get(route('medical-records.show', $appointment))
        ->assertOk();

    $this->actingAs($otherPatient)
        ->get(route('medical-records.show', $appointment))
        ->assertForbidden();

    $this->actingAs($otherDoctor)
        ->get(route('medical-records.show', $appointment))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('medical-records.show', $appointment))
        ->assertOk()
        ->assertSee('Diagnóstico de prueba')
        ->assertSee('Notas para consulta')
        ->assertDontSee('Completar o editar expediente')
        ->assertDontSee('Guardar expediente');
});

it('lets the assigned doctor save notes and lets the patient view escaped notes', function () {
    $patient = medicalRecordTestUser('cliente');
    $doctor = medicalRecordTestUser('doctor');
    $appointment = medicalRecordTestAppointment($patient, $doctor);
    $diagnosis = "<script>alert('diagnostico')</script>";
    $medicalNotes = "<script>alert('nota privada')</script>";
    $treatment = "<script>alert('tratamiento')</script>";
    $recommendations = "<script>alert('recomendaciones')</script>";

    $this->actingAs($doctor)
        ->patch(route('medical-records.update', $appointment), [
            'diagnostico' => $diagnosis,
            'observaciones_medicas' => $medicalNotes,
            'tratamiento_indicaciones' => $treatment,
            'recomendaciones' => $recommendations,
            'requiere_seguimiento' => '1',
        ])
        ->assertRedirect(route('medical-records.show', $appointment));

    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'diagnostico' => $diagnosis,
        'observaciones_medicas' => $medicalNotes,
        'tratamiento_indicaciones' => $treatment,
        'recomendaciones' => $recommendations,
        'requiere_seguimiento' => true,
    ]);

    $this->actingAs($patient)
        ->get(route('medical-records.show', $appointment))
        ->assertOk()
        ->assertSee($diagnosis)
        ->assertDontSee($diagnosis, false)
        ->assertSee($medicalNotes)
        ->assertDontSee($medicalNotes, false)
        ->assertSee($treatment)
        ->assertDontSee($treatment, false)
        ->assertSee($recommendations)
        ->assertDontSee($recommendations, false)
        ->assertSee('Sí requiere seguimiento.')
        ->assertDontSee('Completar o editar expediente');
});

it('forbids patients, administrators, and other doctors from changing a medical record', function (string $roleName) {
    $patient = medicalRecordTestUser('cliente');
    $doctor = medicalRecordTestUser('doctor');
    $otherDoctor = medicalRecordTestUser('doctor');
    $admin = medicalRecordTestUser('admin');
    $appointment = medicalRecordTestAppointment($patient, $doctor);

    $unauthorizedUser = match ($roleName) {
        'cliente' => $patient,
        'doctor' => $otherDoctor,
        default => $admin,
    };

    $this->actingAs($unauthorizedUser)
        ->patch(route('medical-records.update', $appointment), [
            'diagnostico' => 'No autorizado',
            'observaciones_medicas' => 'No autorizado',
            'tratamiento_indicaciones' => 'No autorizado',
            'recomendaciones' => 'No autorizado',
            'requiere_seguimiento' => '1',
        ])
        ->assertForbidden();

    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'diagnostico' => null,
        'observaciones_medicas' => null,
        'tratamiento_indicaciones' => null,
        'recomendaciones' => null,
        'requiere_seguimiento' => null,
    ]);
})->with([
    'patient' => 'cliente',
    'other doctor' => 'doctor',
    'administrator' => 'admin',
]);

it('does not let a doctor write notes before the consultation is completed', function () {
    $patient = medicalRecordTestUser('cliente');
    $doctor = medicalRecordTestUser('doctor');
    $appointment = medicalRecordTestAppointment($patient, $doctor, 'confirmada');

    $this->actingAs($doctor)
        ->patch(route('medical-records.update', $appointment), [
            'diagnostico' => 'Notas anticipadas',
            'observaciones_medicas' => 'Notas anticipadas',
            'tratamiento_indicaciones' => 'Notas anticipadas',
            'recomendaciones' => 'Notas anticipadas',
            'requiere_seguimiento' => '1',
        ])
        ->assertForbidden();

    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'diagnostico' => null,
        'observaciones_medicas' => null,
        'tratamiento_indicaciones' => null,
        'recomendaciones' => null,
        'requiere_seguimiento' => null,
    ]);
});

it('lists only the authenticated patient completed consultations', function () {
    $patient = medicalRecordTestUser('cliente');
    $otherPatient = medicalRecordTestUser('cliente');
    $doctor = medicalRecordTestUser('doctor');
    $ownCompleted = medicalRecordTestAppointment($patient, $doctor, 'completada', 'Consulta propia completada');
    $otherCompleted = medicalRecordTestAppointment($otherPatient, $doctor, 'completada', 'Consulta ajena completada');
    $ownPending = medicalRecordTestAppointment($patient, $doctor, 'pendiente', 'Consulta pendiente');
    $privateSymptom = "<script>alert('síntoma')</script>";
    $ownCompleted->update(['motivo' => $privateSymptom]);

    $this->actingAs($patient)
        ->get(route('medical-history.index'))
        ->assertOk()
        ->assertSee(Carbon::parse($ownCompleted->fecha_hora)->format('d/m/Y - h:i A'))
        ->assertSee($doctor->name)
        ->assertSee($privateSymptom)
        ->assertDontSee($privateSymptom, false)
        ->assertSee('Ver detalles')
        ->assertDontSee($ownCompleted->service->nombre)
        ->assertDontSee('Consulta ajena completada')
        ->assertDontSee('Consulta pendiente');
});

it('denies the medical history to administrators and doctors', function (string $roleName) {
    $user = medicalRecordTestUser($roleName);

    $this->actingAs($user)
        ->get(route('medical-history.index'))
        ->assertForbidden();
})->with([
    'doctor' => 'doctor',
    'administrator' => 'admin',
]);

it('defines the medical record permission matrix in the appointment policy', function () {
    $patient = medicalRecordTestUser('cliente');
    $otherPatient = medicalRecordTestUser('cliente');
    $doctor = medicalRecordTestUser('doctor');
    $otherDoctor = medicalRecordTestUser('doctor');
    $admin = medicalRecordTestUser('admin');
    $completedAppointment = medicalRecordTestAppointment($patient, $doctor);
    $pendingAppointment = medicalRecordTestAppointment($patient, $doctor, 'pendiente');
    $policy = new AppointmentPolicy;

    expect($policy->viewPatientHistory($patient))->toBeTrue()
        ->and($policy->viewPatientHistory($doctor))->toBeFalse()
        ->and($policy->viewPatientHistory($admin))->toBeFalse()
        ->and($policy->viewMedicalRecord($patient, $completedAppointment))->toBeTrue()
        ->and($policy->viewMedicalRecord($otherPatient, $completedAppointment))->toBeFalse()
        ->and($policy->viewMedicalRecord($doctor, $completedAppointment))->toBeTrue()
        ->and($policy->viewMedicalRecord($otherDoctor, $completedAppointment))->toBeFalse()
        ->and($policy->viewMedicalRecord($admin, $completedAppointment))->toBeTrue()
        ->and($policy->viewMedicalRecord($patient, $pendingAppointment))->toBeFalse()
        ->and($policy->viewMedicalRecord($admin, $pendingAppointment))->toBeFalse()
        ->and($policy->updateMedicalRecord($doctor, $completedAppointment))->toBeTrue()
        ->and($policy->updateMedicalRecord($otherDoctor, $completedAppointment))->toBeFalse()
        ->and($policy->updateMedicalRecord($patient, $completedAppointment))->toBeFalse()
        ->and($policy->updateMedicalRecord($admin, $completedAppointment))->toBeFalse()
        ->and($policy->updateMedicalRecord($doctor, $pendingAppointment))->toBeFalse();
});
