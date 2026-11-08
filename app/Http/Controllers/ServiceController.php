<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

/**
 * CONTROLADOR: ServiceController
 * Administración del catálogo de servicios médicos universitarios.
 */
class ServiceController extends Controller
{
    /**
     * Muestra el catálogo de servicios médicos.
     */
    public function index()
    {
        $services = Service::all();
        return view('services.index', compact('services'));
    }

    /**
     * Registra un nuevo servicio en el sistema.
     */
    public function store(Request $request)
    {
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
    public function toggle(Service $service)
    {
        $service->update(['activo' => !$service->activo]);

        return redirect()->back()->with('success', 'El estado del servicio ha sido actualizado.');
    }
}
