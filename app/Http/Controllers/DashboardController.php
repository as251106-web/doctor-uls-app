<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user?->isCliente()) {
            $appointmentsByStatus = Appointment::query()
                ->where('patient_id', $user->id)
                ->select('estado')
                ->selectRaw('COUNT(*) as total')
                ->groupBy('estado')
                ->pluck('total', 'estado');

            $upcomingAppointments = Appointment::query()
                ->with(['doctor:id,name', 'service:id,nombre'])
                ->where('patient_id', $user->id)
                ->whereIn('estado', ['pendiente', 'confirmada'])
                ->where('fecha_hora', '>=', now())
                ->orderBy('fecha_hora')
                ->limit(3)
                ->get();

            return view('patient.dashboard', [
                'totalAppointmentCount' => $appointmentsByStatus->sum(),
                'pendingAppointmentCount' => $appointmentsByStatus->get('pendiente', 0),
                'confirmedAppointmentCount' => $appointmentsByStatus->get('confirmada', 0),
                'completedAppointmentCount' => $appointmentsByStatus->get('completada', 0),
                'cancelledAppointmentCount' => $appointmentsByStatus->get('cancelada', 0),
                'upcomingAppointments' => $upcomingAppointments,
            ]);
        }

        if (! $user?->isAdmin()) {
            return redirect()->route('appointments.index');
        }

        $appointmentsByStatus = Appointment::query()
            ->select('estado')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $roleCounts = Role::query()
            ->withCount('users')
            ->whereIn('nombre', ['doctor', 'cliente'])
            ->get()
            ->keyBy('nombre');

        return view('dashboard', [
            'totalAppointmentCount' => $appointmentsByStatus->sum(),
            'todayAppointmentCount' => Appointment::query()
                ->whereDate('fecha_hora', today())
                ->count(),
            'pendingAppointmentCount' => $appointmentsByStatus->get('pendiente', 0),
            'unassignedAppointmentCount' => Appointment::query()
                ->where('estado', 'pendiente')
                ->whereNull('doctor_id')
                ->count(),
            'confirmedAppointmentCount' => $appointmentsByStatus->get('confirmada', 0),
            'completedAppointmentCount' => $appointmentsByStatus->get('completada', 0),
            'cancelledAppointmentCount' => $appointmentsByStatus->get('cancelada', 0),
            'doctorCount' => $roleCounts->get('doctor')?->users_count ?? 0,
            'patientCount' => $roleCounts->get('cliente')?->users_count ?? 0,
            'activeServiceCount' => Service::query()->where('activo', true)->count(),
        ]);
    }
}
