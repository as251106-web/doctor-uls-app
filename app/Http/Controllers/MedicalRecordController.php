<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MedicalRecordController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewPatientHistory', Appointment::class);

        $appointments = Appointment::query()
            ->select(['id', 'patient_id', 'doctor_id', 'fecha_hora', 'motivo'])
            ->with('doctor:id,name')
            ->where('patient_id', Auth::id())
            ->where('estado', 'completada')
            ->orderByDesc('fecha_hora')
            ->get();

        return view('patient.medical-history', compact('appointments'));
    }

    public function show(Appointment $appointment): View
    {
        Gate::authorize('viewMedicalRecord', $appointment);
        $appointment->load(['patient:id,name,carnet', 'doctor:id,name', 'service:id,nombre']);

        return view('medical-records.show', compact('appointment'));
    }

    public function update(Request $request, Appointment $appointment): RedirectResponse
    {
        Gate::authorize('updateMedicalRecord', $appointment);

        $validated = $request->validate([
            'diagnostico' => ['nullable', 'string', 'max:5000'],
            'observaciones_medicas' => ['required', 'string', 'max:5000'],
            'tratamiento_indicaciones' => ['nullable', 'string', 'max:5000'],
            'recomendaciones' => ['nullable', 'string', 'max:5000'],
            'requiere_seguimiento' => ['sometimes', 'boolean'],
        ]);

        $appointment->update($validated);

        return redirect()
            ->route('medical-records.show', $appointment)
            ->with('success', 'El expediente médico se guardó correctamente.');
    }
}
