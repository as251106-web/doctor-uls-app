<x-layout>
    <div class="max-w-6xl mx-auto">
        <!-- Encabezado -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-extrabold text-blue-900">Gestión de Citas Médicas</h1>
                <p class="text-slate-600 text-sm mt-1">
                    @if(Auth::user()->isAdmin() || Auth::user()->isDoctor())
                        Listado general de citas registradas en el sistema Doctor ULS.
                    @else
                        Historial y seguimiento de tus citas médicas solicitadas.
                    @endif
                </p>
            </div>

            @if(Auth::user()->isCliente())
                <a href="{{ route('appointments.create') }}" class="inline-flex items-center gap-2 bg-blue-900 hover:bg-blue-800 text-white font-bold px-5 py-2.5 rounded-xl transition shadow">
                    <span>➕</span> Agendar Nueva Cita
                </a>
            @endif
        </div>

        <!-- Tabla / Listado de Citas -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            @if($appointments->isEmpty())
                <div class="text-center py-12 px-4">
                    <span class="text-4xl block mb-3">📅</span>
                    <h3 class="text-lg font-bold text-slate-700">No hay citas registradas</h3>
                    <p class="text-slate-500 text-sm mt-1">Actualmente no hay citas médicas programadas.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-100 text-slate-700 text-xs font-bold uppercase border-b border-slate-200">
                                @if(Auth::user()->isAdmin() || Auth::user()->isDoctor())
                                    <th class="py-3 px-4">Paciente</th>
                                    <th class="py-3 px-4">Carné</th>
                                @endif
                                <th class="py-3 px-4">Servicio</th>
                                <th class="py-3 px-4">Fecha y Hora</th>
                                <th class="py-3 px-4">Motivo</th>
                                <th class="py-3 px-4">Estado</th>
                                <th class="py-3 px-4 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-sm">
                            @foreach($appointments as $appointment)
                                <tr class="hover:bg-slate-50 transition">
                                    @if(Auth::user()->isAdmin() || Auth::user()->isDoctor())
                                        <td class="py-3 px-4 font-bold text-slate-800">{{ $appointment->patient->name ?? 'N/A' }}</td>
                                        <td class="py-3 px-4 text-slate-600 font-mono">{{ $appointment->patient->carnet ?? 'N/A' }}</td>
                                    @endif
                                    <td class="py-3 px-4 font-semibold text-blue-900">{{ $appointment->service->nombre }}</td>
                                    <td class="py-3 px-4 text-slate-700">
                                        {{ \Carbon\Carbon::parse($appointment->fecha_hora)->format('d/m/Y - h:i A') }}
                                    </td>
                                    <td class="py-3 px-4 text-slate-600">{{ $appointment->motivo ?? 'Sin especificación' }}</td>
                                    <td class="py-3 px-4">
                                        @if($appointment->estado === 'pendiente')
                                            <span class="inline-block bg-amber-100 text-amber-800 text-xs px-2.5 py-1 rounded-full font-bold border border-amber-200">Pendiente</span>
                                        @elseif($appointment->estado === 'confirmada')
                                            <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2.5 py-1 rounded-full font-bold border border-blue-200">Confirmada</span>
                                        @elseif($appointment->estado === 'completada')
                                            <span class="inline-block bg-emerald-100 text-emerald-800 text-xs px-2.5 py-1 rounded-full font-bold border border-emerald-200">Completada</span>
                                        @else
                                            <span class="inline-block bg-rose-100 text-rose-800 text-xs px-2.5 py-1 rounded-full font-bold border border-rose-200">Cancelada</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        @if(Auth::user()->isAdmin() || Auth::user()->isDoctor())
                                            <!-- Selector de Estado para Doctor / Admin -->
                                            <form action="{{ route('appointments.updateStatus', $appointment) }}" method="POST" class="inline-flex items-center gap-1">
                                                @csrf
                                                @method('PATCH')
                                                <select name="estado" onchange="this.form.submit()" class="text-xs rounded-lg border-slate-300 py-1 px-2 focus:ring-blue-900 font-semibold bg-white shadow-sm">
                                                    <option value="pendiente" {{ $appointment->estado === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                                    <option value="confirmada" {{ $appointment->estado === 'confirmada' ? 'selected' : '' }}>Confirmar</option>
                                                    <option value="completada" {{ $appointment->estado === 'completada' ? 'selected' : '' }}>Completar</option>
                                                    <option value="cancelada" {{ $appointment->estado === 'cancelada' ? 'selected' : '' }}>Cancelar</option>
                                                </select>
                                            </form>
                                        @else
                                            <!-- Botón Cancelar para Cliente -->
                                            @if($appointment->estado !== 'cancelada' && $appointment->estado !== 'completada')
                                                <form action="{{ route('appointments.cancel', $appointment) }}" method="POST" class="inline" onsubmit="return confirm('¿Seguro que deseas cancelar esta cita médica?');">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="text-rose-600 hover:text-rose-800 text-xs font-bold hover:underline">
                                                        Cancelar
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-slate-400 text-xs">—</span>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-layout>
