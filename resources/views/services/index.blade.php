<x-layout>
    <div class="max-w-6xl mx-auto space-y-8">
        <!-- Encabezado -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-blue-900">Catálogo de Servicios Médicos ULS</h1>
                <p class="text-slate-600 text-sm mt-1">Administración de atención médica preventiva y primaria del campus.</p>
            </div>
        </div>

        <!-- Formulario para agregar un nuevo servicio (Solo Admin/Doctor) -->
        @if(Auth::user()->isAdmin() || Auth::user()->isDoctor())
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <h2 class="text-lg font-bold text-blue-900 mb-4 flex items-center gap-2">
                    <span>➕</span> Registrar Nuevo Servicio Médico
                </h2>
                <form action="{{ route('services.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @csrf
                    <div>
                        <label for="nombre" class="block text-xs font-bold text-slate-700 uppercase mb-1">Nombre del Servicio</label>
                        <input type="text" name="nombre" id="nombre" required placeholder="Ej: Odontología Preventiva" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 text-sm">
                    </div>
                    <div>
                        <label for="descripcion" class="block text-xs font-bold text-slate-700 uppercase mb-1">Descripción</label>
                        <input type="text" name="descripcion" id="descripcion" required placeholder="Descripción breve..." class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 text-sm">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-blue-900 hover:bg-blue-800 text-white font-bold py-2.5 px-4 rounded-xl transition shadow text-sm">
                            Guardar Servicio
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Tabla de Servicios Registrados -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100 text-slate-700 text-xs font-bold uppercase border-b border-slate-200">
                            <th class="py-3 px-4">Servicio</th>
                            <th class="py-3 px-4">Descripción</th>
                            <th class="py-3 px-4">Estado</th>
                            @if(Auth::user()->isAdmin() || Auth::user()->isDoctor())
                                <th class="py-3 px-4 text-right">Acción</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-sm">
                        @foreach($services as $service)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="py-3 px-4 font-bold text-blue-900">{{ $service->nombre }}</td>
                                <td class="py-3 px-4 text-slate-600">{{ $service->descripcion }}</td>
                                <td class="py-3 px-4">
                                    @if($service->activo)
                                        <span class="inline-block bg-emerald-100 text-emerald-800 text-xs px-2.5 py-1 rounded-full font-bold">Disponible</span>
                                    @else
                                        <span class="inline-block bg-slate-100 text-slate-600 text-xs px-2.5 py-1 rounded-full font-bold">Inactivo</span>
                                    @endif
                                </td>
                                @if(Auth::user()->isAdmin() || Auth::user()->isDoctor())
                                    <td class="py-3 px-4 text-right">
                                        <form action="{{ route('services.toggle', $service) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-xs font-bold px-3 py-1.5 rounded-lg border {{ $service->activo ? 'border-amber-300 text-amber-700 hover:bg-amber-50' : 'border-emerald-300 text-emerald-700 hover:bg-emerald-50' }}">
                                                {{ $service->activo ? 'Desactivar' : 'Activar' }}
                                            </button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layout>
