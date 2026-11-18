<x-layout>
    <div class="mx-auto max-w-7xl space-y-8">
        <header class="space-y-2">
            <h1 class="text-3xl font-extrabold text-blue-900">Estado del sistema</h1>
            <p class="text-sm text-slate-600">Resumen de citas, pacientes, doctores y servicios de Doctor ULS.</p>
        </header>

        <section aria-label="Resumen del sistema" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['label' => 'Citas registradas', 'value' => $totalAppointmentCount, 'color' => 'text-blue-900'],
                ['label' => 'Citas para hoy', 'value' => $todayAppointmentCount, 'color' => 'text-indigo-700'],
                ['label' => 'Citas pendientes', 'value' => $pendingAppointmentCount, 'color' => 'text-amber-700'],
                ['label' => 'Sin doctor asignado', 'value' => $unassignedAppointmentCount, 'color' => 'text-orange-700'],
                ['label' => 'Citas confirmadas', 'value' => $confirmedAppointmentCount, 'color' => 'text-sky-700'],
                ['label' => 'Citas completadas', 'value' => $completedAppointmentCount, 'color' => 'text-emerald-700'],
                ['label' => 'Citas canceladas', 'value' => $cancelledAppointmentCount, 'color' => 'text-rose-700'],
                ['label' => 'Doctores', 'value' => $doctorCount, 'color' => 'text-violet-700'],
                ['label' => 'Pacientes', 'value' => $patientCount, 'color' => 'text-cyan-700'],
                ['label' => 'Servicios activos', 'value' => $activeServiceCount, 'color' => 'text-teal-700'],
            ] as $card)
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-semibold text-slate-600">{{ $card['label'] }}</p>
                    <p class="mt-3 text-3xl font-extrabold {{ $card['color'] }}">{{ $card['value'] }}</p>
                </div>
            @endforeach
        </section>
    </div>
</x-layout>
