<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->isAdmin()) {
            $appointments = Appointment::with(['patient', 'doctor', 'service'])
                ->select(['id', 'patient_id', 'doctor_id', 'service_id', 'fecha_hora', 'estado', 'motivo', 'created_at', 'updated_at'])
                ->orderBy('fecha_hora', 'desc')
                ->get();
        } elseif ($user->isDoctor()) {
            $appointments = Appointment::with(['patient', 'doctor', 'service'])
                ->where('doctor_id', $user->id)
                ->orderBy('fecha_hora', 'desc')
                ->get();
        } else {
            $appointments = Appointment::with(['doctor', 'service'])
                ->where('patient_id', $user->id)
                ->orderBy('fecha_hora', 'desc')
                ->get();
        }

        return view('appointments.index', compact('appointments'));
    }

    public function create(): View
    {
        Gate::authorize('create', Appointment::class);
        /** @var User $user */
        $user = Auth::user();

        $services = Service::query()
            ->where('activo', true)
            ->when(
                $user->isDoctor(),
                fn (EloquentBuilder $query) => $query->whereHas(
                    'doctors',
                    fn (EloquentBuilder $doctors) => $doctors->where('users.id', $user->id),
                ),
            )
            ->with(['doctors' => fn ($query) => $query->withCount([
                'doctorAppointments as active_appointments_count' => fn ($appointments) => $appointments
                    ->whereIn('estado', ['pendiente', 'confirmada']),
            ])])
            ->get();

        $patients = $user->isDoctor()
            ? User::query()
                ->whereHas('role', fn (EloquentBuilder $query) => $query->where('nombre', 'cliente'))
                ->select(['id', 'name', 'carnet'])
                ->orderBy('name')
                ->get()
            : collect();

        return view('appointments.create', compact('services', 'patients'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Appointment::class);
        /** @var User $user */
        $user = Auth::user();

        $rules = [
            'service_id' => [
                'required',
                'integer',
                Rule::exists('services', 'id')->where(fn (QueryBuilder $query) => $query->where('activo', true)),
            ],
            'fecha_hora' => ['required', 'date', 'after:now'],
            'motivo' => ['nullable', 'string', 'max:500'],
        ];

        if ($user->isDoctor()) {
            $rules['patient_id'] = ['required', 'integer', 'exists:users,id'];
        }

        $validated = $request->validate($rules);

        $startTime = Carbon::parse($validated['fecha_hora']);
        $appointment = DB::transaction(function () use ($validated, $startTime, $user): Appointment {
            $service = Service::findOrFail($validated['service_id']);

            if ($user->isDoctor()) {
                $patient = User::query()
                    ->whereKey($validated['patient_id'])
                    ->whereHas('role', fn (EloquentBuilder $query) => $query->where('nombre', 'cliente'))
                    ->first();

                if ($patient === null) {
                    throw ValidationException::withMessages([
                        'patient_id' => 'Selecciona una cuenta con rol de paciente.',
                    ]);
                }

                if (! $user->services()->where('services.id', $service->id)->exists()) {
                    throw ValidationException::withMessages([
                        'service_id' => 'Solo puedes agendar citas para los servicios asignados a tu cuenta.',
                    ]);
                }

                $doctor = $user;
            } else {
                $patient = $user;
                $doctor = $this->findServiceDoctor($service);
            }

            if ($doctor === null) {
                throw ValidationException::withMessages([
                    'service_id' => 'Este servicio no tiene un doctor asignado y no se puede agendar por el momento.',
                ]);
            }

            return Appointment::create([
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'service_id' => $service->id,
                'fecha_hora' => $startTime,
                'motivo' => $validated['motivo'] ?? null,
                'estado' => 'pendiente',
            ]);
        });

        return redirect()
            ->route('appointments.index')
            ->with(
                'success',
                $user->isDoctor()
                    ? 'La cita quedó registrada en tu agenda para el paciente seleccionado.'
                    : 'Tu cita quedó registrada en la agenda del doctor asignado.',
            );
    }

    public function reschedule(Request $request, Appointment $appointment): RedirectResponse
    {
        Gate::authorize('reschedule', $appointment);

        $validated = $request->validate([
            'fecha_hora' => ['required', 'date', 'after:now'],
        ]);
        $startTime = Carbon::parse($validated['fecha_hora']);

        DB::transaction(function () use ($appointment, $startTime): void {
            $lockedAppointment = Appointment::query()
                ->whereKey($appointment->id)
                ->lockForUpdate()
                ->firstOrFail();

            Gate::authorize('reschedule', $lockedAppointment);

            $lockedAppointment->update([
                'fecha_hora' => $startTime,
            ]);
        });

        return redirect()
            ->route('appointments.index')
            ->with('success', 'La cita fue reagendada correctamente.');
    }

    public function claim(Request $request, Appointment $appointment): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user->isDoctor(), 403, 'Solo un doctor puede tomar esta solicitud.');

        $validated = $request->validate([
            'fecha_hora' => ['nullable', 'date', 'after:now'],
            'take_now' => ['sometimes', 'boolean'],
        ]);

        $startTime = filter_var($validated['take_now'] ?? false, FILTER_VALIDATE_BOOLEAN)
            ? Carbon::now()->addMinute()->startOfMinute()
            : Carbon::parse($validated['fecha_hora'] ?? $appointment->fecha_hora);

        if ($startTime->lessThanOrEqualTo(now())) {
            throw ValidationException::withMessages([
                'fecha_hora' => 'La fecha solicitada ya pasó. Elige otro horario o toma la cita ahora.',
            ]);
        }

        DB::transaction(function () use ($appointment, $user, $startTime): void {
            $lockedAppointment = Appointment::query()
                ->whereKey($appointment->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($lockedAppointment->doctor_id === null && $lockedAppointment->estado === 'pendiente', 409, 'Esta solicitud ya fue tomada o no está disponible.');

            $doctor = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            abort_unless($doctor->isDoctor(), 403, 'Solo un doctor puede tomar esta solicitud.');
            abort_unless(
                $doctor->services()->where('services.id', $lockedAppointment->service_id)->exists(),
                403,
                'Este servicio no está asignado a tu cuenta.',
            );

            if ($doctor->doctorAppointments()->whereIn('estado', ['pendiente', 'confirmada'])->exists()) {
                throw ValidationException::withMessages([
                    'fecha_hora' => 'No estás disponible para tomar otra consulta hasta completar la actual.',
                ]);
            }

            $lockedAppointment->update([
                'doctor_id' => $doctor->id,
                'fecha_hora' => $startTime,
                'estado' => 'confirmada',
            ]);
        });

        return redirect()->route('appointments.index')->with('success', 'Tomaste la solicitud y quedó asignada a tu agenda.');
    }

    /**
     * Permite al administrador cambiar cualquier estado y al doctor el de su propia cita.
     */
    public function updateStatus(Request $request, Appointment $appointment): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->isAdmin() && (! $user->isDoctor() || $appointment->doctor_id !== $user->id)) {
            abort(403, 'No tienes permisos para realizar esta acción.');
        }

        if ($user->isDoctor() && in_array($appointment->estado, ['completada', 'cancelada'], true)) {
            abort(403, 'Esta cita ya está cerrada.');
        }

        $allowedStatuses = $user->isAdmin()
            ? ['pendiente', 'confirmada', 'completada', 'cancelada']
            : ['completada', 'cancelada'];

        $validated = $request->validate([
            'estado' => ['required', Rule::in($allowedStatuses)],
        ]);

        $appointment->update(['estado' => $validated['estado']]);

        return redirect()->route('appointments.index')->with('success', 'El estado de la cita ha sido actualizado correctamente.');
    }

    public function cancel(Appointment $appointment): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $isAuthorized = $user->isAdmin()
            || ($user->isDoctor() && $appointment->doctor_id === $user->id)
            || ($user->isCliente() && $appointment->patient_id === $user->id);

        if (! $isAuthorized || in_array($appointment->estado, ['completada', 'cancelada'], true)) {
            abort(403, 'No tienes autorización para cancelar esta cita.');
        }

        $appointment->update(['estado' => 'cancelada']);

        return redirect()->route('appointments.index')->with('success', 'La cita seleccionada ha sido cancelada correctamente.');
    }

    private function findServiceDoctor(Service $service): ?User
    {
        return $service->doctors()
            ->whereHas('role', fn (EloquentBuilder $query) => $query->where('nombre', 'doctor'))
            ->orderBy('users.id')
            ->first();
    }
}
