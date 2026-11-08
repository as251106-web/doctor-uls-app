<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user && ($user->isAdmin() || $user->isDoctor())) {
            $appointments = Appointment::with(['patient', 'service'])->orderBy('fecha_hora', 'desc')->get();
        } else {
            $appointments = Appointment::with(['service'])->where('patient_id', $user->id)->orderBy('fecha_hora', 'desc')->get();
        }

        return view('appointments.index', compact('appointments'));
    }

    public function create()
    {
        $services = Service::where('activo', true)->get();
        return view('appointments.create', compact('services'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'fecha_hora' => 'required|date|after:now',
            'motivo' => 'nullable|string|max:500',
        ]);

        Appointment::create([
            'patient_id' => Auth::id(),
            'service_id' => $request->service_id,
            'fecha_hora' => $request->fecha_hora,
            'motivo' => $request->motivo,
            'estado' => 'pendiente',
        ]);

        return redirect()->route('appointments.index')->with('success', 'Tu cita médica en Doctor ULS ha sido agendada exitosamente.');
    }

    /**
     * Permite al Doctor o Admin cambiar el estado de una cita.
     */
    public function updateStatus(Request $request, Appointment $appointment)
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user->isAdmin() && !$user->isDoctor()) {
            abort(403, 'No tienes permisos para realizar esta acción.');
        }

        $request->validate([
            'estado' => 'required|in:pendiente,confirmada,completada,cancelada',
        ]);

        $appointment->update(['estado' => $request->estado]);

        return redirect()->route('appointments.index')->with('success', 'El estado de la cita ha sido actualizado correctamente.');
    }

    public function cancel(Appointment $appointment)
    {
        /** @var User $user */
        $user = Auth::user();

        if (Auth::id() !== $appointment->patient_id && !$user->isAdmin() && !$user->isDoctor()) {
            abort(403, 'No tienes autorización para cancelar esta cita.');
        }

        $appointment->update(['estado' => 'cancelada']);

        return redirect()->route('appointments.index')->with('success', 'La cita seleccionada ha sido cancelada correctamente.');
    }
}
