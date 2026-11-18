<x-layout>
    <div class="mx-auto max-w-6xl space-y-6">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-extrabold text-blue-900">Historial médico</h1>
                <p class="mt-1 text-sm text-slate-600">Consulta los datos de tus consultas y abre sus detalles médicos.</p>
            </div>
            <a href="{{ route('appointments.index') }}" class="font-bold text-blue-900 hover:underline">Volver a mis citas</a>
        </header>

        @if($appointments->isEmpty())
            <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-slate-600 shadow-sm">
                Todavía no tienes consultas completadas.
            </div>
        @else
            <section class="grid grid-cols-1 gap-5 lg:grid-cols-2" aria-label="Consultas completadas">
                @foreach($appointments as $appointment)
                    <article class="flex flex-col gap-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <dl class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fecha de consulta</dt>
                                <dd class="mt-1 font-semibold text-slate-800">{{ \Carbon\Carbon::parse($appointment->fecha_hora)->format('d/m/Y - h:i A') }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Doctor encargado</dt>
                                <dd class="mt-1 font-semibold text-slate-800">{{ $appointment->doctor->name ?? 'No asignado' }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Síntoma o problema</dt>
                                <dd class="mt-1 whitespace-pre-line text-slate-700">{{ $appointment->motivo ?: 'Sin especificación' }}</dd>
                            </div>
                        </dl>

                        <a href="{{ route('medical-records.show', $appointment) }}" class="inline-flex self-start rounded-xl bg-blue-900 px-4 py-2.5 font-bold text-white transition hover:bg-blue-800">
                            Ver detalles
                        </a>
                    </article>
                @endforeach
            </section>
        @endif
    </div>
</x-layout>
