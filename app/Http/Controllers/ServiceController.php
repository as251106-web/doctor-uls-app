<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CONTROLADOR: ServiceController
 * Administración del catálogo de servicios médicos universitarios.
 */
class ServiceController extends Controller
{
    /**
     * Muestra el catálogo de servicios médicos.
     */
    public function index(): View
    {
        $this->authorizeAdmin();

        $services = Service::with('doctors')->get();

        return view('services.index', compact('services'));
    }

    /**
     * Registra un nuevo servicio en el sistema.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string',
        ]);

        Service::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'activo' => true,
        ]);

        return redirect()->back()->with('success', 'Nuevo servicio médico agregado exitosamente.');
    }

    /**
     * Activa o desactiva la disponibilidad de un servicio.
     */
    public function toggle(Service $service): RedirectResponse
    {
        $this->authorizeAdmin();

        $service->update(['activo' => ! $service->activo]);

        return redirect()->back()->with('success', 'El estado del servicio ha sido actualizado.');
    }

    private function authorizeAdmin(): void
    {
        /** @var User|null $user */
        $user = auth()->user();

        abort_unless($user?->isAdmin(), 403, 'Acceso no autorizado.');
    }
}
