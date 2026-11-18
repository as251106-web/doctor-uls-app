<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function create(User $user): bool
    {
        return $user->isCliente() || $user->isDoctor();
    }

    public function reschedule(User $user, Appointment $appointment): bool
    {
        return $user->isDoctor()
            && $appointment->doctor_id === $user->id
            && in_array($appointment->estado, ['pendiente', 'confirmada'], true);
    }

    public function viewPatientHistory(User $user): bool
    {
        return $user->isCliente();
    }

    public function viewMedicalRecord(User $user, Appointment $appointment): bool
    {
        if ($appointment->estado !== 'completada') {
            return false;
        }

        return $user->isAdmin()
            || ($user->isCliente() && $appointment->patient_id === $user->id)
            || ($user->isDoctor() && $appointment->doctor_id === $user->id);
    }

    public function updateMedicalRecord(User $user, Appointment $appointment): bool
    {
        return $user->isDoctor()
            && $appointment->doctor_id === $user->id
            && $appointment->estado === 'completada';
    }
}
