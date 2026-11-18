<x-layout>
    <div class="mx-auto max-w-7xl space-y-8">
        <header class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-2">
                <h1 class="text-3xl font-extrabold text-blue-900">Resumen de mi cuenta</h1>
                <p class="text-sm text-slate-600">Consulta el estado de tus citas y revisa tus próximas consultas.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('appointments.create') }}" class="rounded-xl bg-blue-900 px-5 py-2.5 font-bold text-white shadow transition hover:bg-blue-800">
                    Agendar cita
                </a>
                <a href="{{ route('appointments.index') }}" class="rounded-xl border border-blue-900 px-5 py-2.5 font-bold text-blue-900 transition hover:bg-blue-50">
                    Ver mis citas
                </a>
                <a href="{{ route('medical-history.index') }}" class="rounded-xl border border-slate-300 px-5 py-2.5 font-bold text-slate-700 transition hover:bg-slate-50">
                    Historial médico
                </a>
            </div>
        </header>

        <section aria-label="Resumen de mis citas" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            @foreach ([
                ['label' => 'Citas registradas', 'value' => $totalAppointmentCount, 'color' => 'text-blue-900'],
                ['label' => 'Pendientes', 'value' => $pendingAppointmentCount, 'color' => 'text-amber-700'],
                ['label' => 'Confirmadas', 'value' => $confirmedAppointmentCount, 'color' => 'text-sky-700'],
                ['label' => 'Completadas', 'value' => $completedAppointmentCount, 'color' => 'text-emerald-700'],
                ['label' => 'Canceladas', 'value' => $cancelledAppointmentCount, 'color' => 'text-rose-700'],
            ] as $card)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-semibold text-slate-600">{{ $card['label'] }}</p>
                    <p class="mt-3 text-3xl font-extrabold {{ $card['color'] }}">{{ $card['value'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="space-y-4">
            <div>
                <h2 class="text-xl font-bold text-blue-900">Próximas consultas</h2>
                <p class="mt-1 text-sm text-slate-600">Tus citas pendientes o confirmadas más cercanas.</p>
            </div>

            @if ($upcomingAppointments->isEmpty())
                <div class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-600 shadow-sm">
                    No tienes consultas próximas. Puedes agendar una cita cuando lo necesites.
                </div>
            @else
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                    @foreach ($upcomingAppointments as $appointment)
                        @php
                            $statusStyles = [
                                'pendiente' => 'border-amber-200 bg-amber-100 text-amber-800',
                                'confirmada' => 'border-blue-200 bg-blue-100 text-blue-800',
                            ];
                            $statusLabels = [
                                'pendiente' => 'Pendiente',
                                'confirmada' => 'Confirmada',
                            ];
                        @endphp

                        <article class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <header class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Consulta</p>
                                    <h3 class="mt-1 text-lg font-bold text-blue-900">{{ $appointment->service->nombre }}</h3>
                                </div>
                                <span class="rounded-full border px-3 py-1 text-xs font-bold {{ $statusStyles[$appointment->estado] ?? 'border-slate-200 bg-slate-100 text-slate-700' }}">
                                    {{ $statusLabels[$appointment->estado] ?? ucfirst($appointment->estado) }}
                                </span>
                            </header>

                            <dl class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fecha y hora</dt>
                                    <dd class="mt-1 font-semibold text-slate-800">{{ \Carbon\Carbon::parse($appointment->fecha_hora)->format('d/m/Y - h:i A') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Doctor</dt>
                                    <dd class="mt-1 font-semibold text-slate-800">{{ $appointment->doctor->name ?? 'Sin asignar' }}</dd>
                                </div>
                            </dl>

                            <div class="rounded-xl bg-slate-50 p-4">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Síntoma o motivo</p>
                                <p class="mt-1 text-sm text-slate-700">{{ $appointment->motivo ?: 'Sin especificación' }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-layout>
