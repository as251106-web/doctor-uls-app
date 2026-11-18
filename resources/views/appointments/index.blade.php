<x-layout>
    {{-- Aquí se arma la pantalla de citas según el rol que inició sesión. --}}
    <div class="max-w-6xl mx-auto space-y-8">
        {{-- El título y el texto cambian para distinguir la agenda del doctor de las citas del paciente. --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-blue-900">
                    @if(Auth::user()->isAdmin() || Auth::user()->isDoctor())
                        Citas médicas
                    @else
                        Mis citas
                    @endif
                </h1>
                <p class="text-slate-600 text-sm mt-1">
                    @if(Auth::user()->isAdmin())
                        Agenda general de citas registradas en el sistema Doctor ULS.
                    @elseif(Auth::user()->isDoctor())
                        Consulta tu agenda y las citas asignadas a tus servicios.
                    @else
                        Historial y seguimiento de tus citas médicas solicitadas.
                    @endif
                </p>
            </div>

            {{-- El paciente agenda para sí mismo y el doctor puede agendar para un paciente registrado. --}}
            @if(Auth::user()->isCliente() || Auth::user()->isDoctor())
                <a href="{{ route('appointments.create') }}" class="inline-flex items-center gap-2 bg-blue-900 hover:bg-blue-800 text-white font-bold px-5 py-2.5 rounded-xl transition shadow">
                    <span>➕</span>
                    {{ Auth::user()->isDoctor() ? 'Agendar cita para paciente' : 'Agendar nueva cita' }}
                </a>
            @endif
        </div>

        {{-- Si una acción falla, mostramos aquí los mensajes para que el usuario sepa qué corregir. --}}
        @if($errors->any())
            <div class="bg-rose-100 border-l-4 border-rose-500 text-rose-800 p-4 rounded-xl">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Los pacientes y doctores ven sus citas en tarjetas; el admin tiene una tabla general abajo. --}}
        @if(!Auth::user()->isAdmin())
            @php
                // Estos arreglos ponen una etiqueta y un color a cada estado de cita.
                $appointmentStatusStyles = [
                    'pendiente' => 'border-amber-200 bg-amber-100 text-amber-800',
                    'confirmada' => 'border-blue-200 bg-blue-100 text-blue-800',
                    'completada' => 'border-emerald-200 bg-emerald-100 text-emerald-800',
                    'cancelada' => 'border-rose-200 bg-rose-100 text-rose-800',
                ];
                $appointmentStatusLabels = [
                    'pendiente' => 'Pendiente',
                    'confirmada' => 'Confirmada',
                    'completada' => 'Completada',
                    'cancelada' => 'Cancelada',
                ];
            @endphp

            {{-- Esta lista incluye solo las citas del paciente o del doctor que inició sesión. --}}
            <section class="space-y-4">
                <div>
                    <h2 class="text-xl font-bold text-blue-900">
                        {{ Auth::user()->isDoctor() ? 'Citas médicas asignadas' : 'Mis citas' }}
                    </h2>
                    <p class="mt-1 text-sm text-slate-600">Consulta el servicio, la fecha, el estado y el síntoma o motivo registrado.</p>
                </div>

                @if($appointments->isEmpty())
                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-10 text-center shadow-sm">
                        <span class="mb-3 block text-4xl">📅</span>
                        <p class="text-sm text-slate-500">No hay citas registradas en este listado.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                        {{-- Mostramos los datos principales de cada cita en una tarjeta. --}}
                        @foreach($appointments as $appointment)
                            <article class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                <header class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Consulta</p>
                                        <h3 class="mt-1 text-lg font-bold text-blue-900">{{ $appointment->service->nombre }}</h3>
                                    </div>
                                    <span class="rounded-full border px-3 py-1 text-xs font-bold {{ $appointmentStatusStyles[$appointment->estado] ?? 'border-slate-200 bg-slate-100 text-slate-700' }}">
                                        {{ $appointmentStatusLabels[$appointment->estado] ?? ucfirst($appointment->estado) }}
                                    </span>
                                </header>

                                {{-- El doctor ve al paciente; el paciente ve al doctor asignado. --}}
                                <dl class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            {{ Auth::user()->isDoctor() ? 'Paciente' : 'Doctor' }}
                                        </dt>
                                        <dd class="mt-1 font-semibold text-slate-800">
                                            @if(Auth::user()->isDoctor())
                                                {{ $appointment->patient->name ?? 'N/A' }}
                                            @else
                                                {{ $appointment->doctor->name ?? 'Sin asignar' }}
                                            @endif
                                        </dd>
                                        @if(Auth::user()->isDoctor())
                                            <dd class="text-xs text-slate-500">{{ $appointment->patient->carnet ?? 'N/A' }}</dd>
                                        @endif
                                    </div>
                                    <div>
                                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fecha y hora</dt>
                                        <dd class="mt-1 font-semibold text-slate-800">{{ \Carbon\Carbon::parse($appointment->fecha_hora)->format('d/m/Y - h:i A') }}</dd>
                                    </div>
                                </dl>

                                {{-- Aquí se conserva el síntoma o motivo que se escribió al agendar. --}}
                                <div class="rounded-xl bg-slate-50 p-4">
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Síntoma o motivo</p>
                                    <p class="mt-1 text-sm text-slate-700">{{ $appointment->motivo ?: 'Sin especificación' }}</p>
                                </div>

                                {{-- Cuando la consulta termina, el paciente o doctor autorizado puede abrir su expediente. --}}
                                @if($appointment->estado === 'completada')
                                    <a href="{{ route('medical-records.show', $appointment) }}" class="inline-flex font-bold text-blue-900 hover:underline">
                                        Ver expediente de la consulta
                                    </a>
                                @endif

                                {{-- El doctor puede cambiar la fecha mientras su cita siga abierta. --}}
                                @if(Auth::user()->isDoctor() && !in_array($appointment->estado, ['completada', 'cancelada'], true))
                                    <form action="{{ route('appointments.reschedule', $appointment) }}" method="POST" class="space-y-2 border-t border-slate-100 pt-4">
                                        @csrf
                                        @method('PATCH')
                                        <label for="nueva_fecha_{{ $appointment->id }}" class="block text-xs font-semibold text-slate-600">Reagendar cita</label>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <input id="nueva_fecha_{{ $appointment->id }}" type="datetime-local" name="fecha_hora" value="{{ \Carbon\Carbon::parse($appointment->fecha_hora)->format('Y-m-d\TH:i') }}" min="{{ now()->format('Y-m-d\TH:i') }}" required class="min-w-0 flex-1 rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-900 focus:ring-blue-900">
                                            <button type="submit" class="rounded-lg border border-blue-900 px-3 py-2 text-xs font-bold text-blue-900 transition hover:bg-blue-50">Reagendar</button>
                                        </div>
                                    </form>

                                    {{-- Desde aquí el doctor termina o cancela una cita que tiene asignada. --}}
                                    <form action="{{ route('appointments.updateStatus', $appointment) }}" method="POST" class="flex items-center justify-between gap-3 border-t border-slate-100 pt-4">
                                        @csrf
                                        @method('PATCH')
                                        <label for="estado_{{ $appointment->id }}" class="text-xs font-semibold text-slate-600">Actualizar estado</label>
                                        <select id="estado_{{ $appointment->id }}" name="estado" onchange="this.form.submit()" class="w-32 shrink-0 rounded-lg border-slate-300 py-1.5 pl-1 pr-8 text-left text-xs font-semibold shadow-sm focus:border-blue-900 focus:ring-blue-900">
                                            <option value="" selected disabled>Pendiente</option>
                                            <option value="completada">Completar</option>
                                            <option value="cancelada">Cancelar</option>
                                        </select>
                                    </form>
                                @elseif(Auth::user()->isCliente() && !in_array($appointment->estado, ['cancelada', 'completada'], true))
                                    {{-- El paciente puede cancelar sus citas que todavía no están cerradas. --}}
                                    <form action="{{ route('appointments.cancel', $appointment) }}" method="POST" class="border-t border-slate-100 pt-4" onsubmit="return confirm('¿Seguro que deseas cancelar esta cita médica?');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-xs font-bold text-rose-600 hover:text-rose-800 hover:underline">Cancelar cita</button>
                                    </form>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

        {{-- El administrador consulta aquí todas las citas y puede abrir los expedientes completados. --}}
        @if(Auth::user()->isAdmin())
        <section class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-5 border-b border-slate-200">
                <h2 class="text-xl font-bold text-blue-900">
                    @if(Auth::user()->isDoctor())
                        Citas médicas asignadas
                    @elseif(Auth::user()->isAdmin())
                        Todas las citas
                    @else
                        Mis citas
                    @endif
                </h2>
            </div>

            @if($appointments->isEmpty())
                <div class="text-center py-10 px-4">
                    <span class="text-4xl block mb-3">📅</span>
                    <p class="text-slate-500 text-sm">No hay citas registradas en este listado.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        {{-- Los encabezados dependen de la información que se muestra al administrador. --}}
                        <thead>
                            <tr class="bg-slate-100 text-slate-700 text-xs font-bold uppercase border-b border-slate-200">
                                @if(Auth::user()->isAdmin() || Auth::user()->isDoctor())
                                    <th class="py-3 px-4">Paciente</th>
                                    <th class="py-3 px-4">Carné</th>
                                @endif
                                @if(Auth::user()->isAdmin() || Auth::user()->isCliente())
                                    <th class="py-3 px-4">Doctor</th>
                                @endif
                                <th class="py-3 px-4">Servicio</th>
                                <th class="py-3 px-4">Fecha y Hora</th>
                                <th class="py-3 px-4">Motivo</th>
                                <th class="py-3 px-4">Estado</th>
                                <th class="py-3 px-4 text-right">Detalles</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-sm">
                            {{-- Cada fila corresponde a una cita registrada en el sistema. --}}
                            @foreach($appointments as $appointment)
                                <tr class="hover:bg-slate-50 transition">
                                    @if(Auth::user()->isAdmin() || Auth::user()->isDoctor())
                                        <td class="py-3 px-4 font-bold text-slate-800">{{ $appointment->patient->name ?? 'N/A' }}</td>
                                        <td class="py-3 px-4 text-slate-600 font-mono">{{ $appointment->patient->carnet ?? 'N/A' }}</td>
                                    @endif
                                    @if(Auth::user()->isAdmin() || Auth::user()->isCliente())
                                        <td class="py-3 px-4 text-slate-600">{{ $appointment->doctor->name ?? 'Sin asignar' }}</td>
                                    @endif
                                    <td class="py-3 px-4 font-semibold text-blue-900">{{ $appointment->service->nombre }}</td>
                                    <td class="py-3 px-4 text-slate-700">{{ \Carbon\Carbon::parse($appointment->fecha_hora)->format('d/m/Y - h:i A') }}</td>
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
                                        @if(Auth::user()->isAdmin() && $appointment->estado === 'completada')
                                            {{-- Si está completada, mostramos el botón para ver el expediente --}}
                                            <a href="{{ route('medical-records.show', $appointment) }}" class="inline-flex font-bold text-blue-900 hover:underline">
                                                Ver detalles
                                            </a>
                                        @else
                                            {{-- Si NO está completada, mostramos el texto indicativo --}}
                                            <span class="text-slate-400 text-xs">Aún pendiente</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
        @endif
    </div>
</x-layout>
