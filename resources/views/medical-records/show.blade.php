<x-layout>
    <div class="mx-auto max-w-3xl space-y-6">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Expediente médico</p>
                <h1 class="mt-1 text-3xl font-extrabold text-blue-900">{{ $appointment->service->nombre }}</h1>
            </div>
            @if(Auth::user()->isCliente())
                <a href="{{ route('medical-history.index') }}" class="font-bold text-blue-900 hover:underline">Volver al historial</a>
            @else
                <a href="{{ route('appointments.index') }}" class="font-bold text-blue-900 hover:underline">Volver a citas</a>
            @endif
        </header>

        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-100 p-4 text-sm font-semibold text-emerald-800" role="status">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-100 p-4 text-sm text-rose-800">
                <ul class="list-inside list-disc space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Paciente</dt>
                    <dd class="mt-1 font-semibold text-slate-800">{{ $appointment->patient->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Doctor</dt>
                    <dd class="mt-1 font-semibold text-slate-800">{{ $appointment->doctor->name ?? 'No asignado' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fecha de consulta</dt>
                    <dd class="mt-1 font-semibold text-slate-800">{{ \Carbon\Carbon::parse($appointment->fecha_hora)->format('d/m/Y - h:i A') }}</dd>
                </div>
            </dl>

            <div class="space-y-2 border-t border-slate-100 pt-5">
                <h2 class="text-lg font-bold text-blue-900">Motivo o síntoma reportado</h2>
                <p class="whitespace-pre-line rounded-xl bg-slate-50 p-4 text-sm text-slate-700">{{ $appointment->motivo ?: 'Sin especificación' }}</p>
            </div>

            @foreach ([
                'Diagnóstico' => $appointment->diagnostico,
                'Notas del doctor' => $appointment->observaciones_medicas,
                'Tratamiento o indicaciones' => $appointment->tratamiento_indicaciones,
                'Recomendaciones' => $appointment->recomendaciones,
            ] as $label => $content)
                <div class="space-y-2 border-t border-slate-100 pt-5">
                    <h2 class="text-lg font-bold text-blue-900">{{ $label }}</h2>
                    <p class="whitespace-pre-line rounded-xl bg-slate-50 p-4 text-sm text-slate-700">{{ $content ?: 'El doctor todavía no ha agregado esta información.' }}</p>
                </div>
            @endforeach

            <div class="border-t border-slate-100 pt-5">
                <h2 class="text-lg font-bold text-blue-900">Seguimiento</h2>
                <p class="mt-2 rounded-xl bg-slate-50 p-4 text-sm text-slate-700">
                    @if(is_null($appointment->requiere_seguimiento))
                        El doctor todavía no ha indicado si requiere seguimiento.
                    @elseif($appointment->requiere_seguimiento)
                        Sí requiere seguimiento.
                    @else
                        No requiere seguimiento.
                    @endif
                </p>
            </div>

            @if(Auth::user()->isDoctor())
                <form action="{{ route('medical-records.update', $appointment) }}" method="POST" class="space-y-4 border-t border-slate-100 pt-5">
                    @csrf
                    @method('PATCH')
                    <h2 class="text-xl font-bold text-blue-900">Completar o editar expediente</h2>

                    <div>
                        <label for="diagnostico" class="mb-1 block text-sm font-bold text-slate-700">Diagnóstico</label>
                        <textarea id="diagnostico" name="diagnostico" rows="3" maxlength="5000" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-900 focus:ring-blue-900">{{ old('diagnostico', $appointment->diagnostico) }}</textarea>
                        <x-input-error :messages="$errors->get('diagnostico')" />
                    </div>

                    <div>
                        <label for="observaciones_medicas" class="mb-1 block text-sm font-bold text-slate-700">Notas del doctor</label>
                        <textarea id="observaciones_medicas" name="observaciones_medicas" rows="5" maxlength="5000" required class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-900 focus:ring-blue-900">{{ old('observaciones_medicas', $appointment->observaciones_medicas) }}</textarea>
                        <x-input-error :messages="$errors->get('observaciones_medicas')" />
                    </div>

                    <div>
                        <label for="tratamiento_indicaciones" class="mb-1 block text-sm font-bold text-slate-700">Tratamiento o indicaciones</label>
                        <textarea id="tratamiento_indicaciones" name="tratamiento_indicaciones" rows="3" maxlength="5000" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-900 focus:ring-blue-900">{{ old('tratamiento_indicaciones', $appointment->tratamiento_indicaciones) }}</textarea>
                        <x-input-error :messages="$errors->get('tratamiento_indicaciones')" />
                    </div>

                    <div>
                        <label for="recomendaciones" class="mb-1 block text-sm font-bold text-slate-700">Recomendaciones</label>
                        <textarea id="recomendaciones" name="recomendaciones" rows="3" maxlength="5000" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-900 focus:ring-blue-900">{{ old('recomendaciones', $appointment->recomendaciones) }}</textarea>
                        <x-input-error :messages="$errors->get('recomendaciones')" />
                    </div>

                    <div class="flex items-start gap-3">
                        <input type="hidden" name="requiere_seguimiento" value="0">
                        <input id="requiere_seguimiento" name="requiere_seguimiento" type="checkbox" value="1" {{ old('requiere_seguimiento', $appointment->requiere_seguimiento) ? 'checked' : '' }} class="mt-1 rounded border-slate-300 text-blue-900 shadow-sm focus:ring-blue-900">
                        <label for="requiere_seguimiento" class="text-sm font-semibold text-slate-700">El paciente requiere seguimiento</label>
                    </div>
                    <x-input-error :messages="$errors->get('requiere_seguimiento')" />

                    <button type="submit" class="rounded-xl bg-blue-900 px-5 py-2.5 font-bold text-white shadow transition hover:bg-blue-800">
                        Guardar expediente
                    </button>
                </form>
            @endif
        </section>
    </div>
</x-layout>
