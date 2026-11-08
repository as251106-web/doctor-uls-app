<x-layout>
    <div class="max-w-2xl mx-auto bg-white p-8 rounded-2xl shadow-sm border border-slate-200 mt-4">
        <h1 class="text-2xl font-extrabold text-blue-900 mb-2">Agendar Cita Médica</h1>
        <p class="text-slate-600 text-sm mb-6">Completa el formulario para reservar tu atención médica en la ULS.</p>

        <form action="{{ route('appointments.store') }}" method="POST" class="space-y-5">
            @csrf

            <!-- Selección de Servicio -->
            <div>
                <label for="service_id" class="block text-sm font-bold text-slate-700 mb-1">Servicio Médico Required</label>
                <select name="service_id" id="service_id" required class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-900 focus:ring-blue-900">
                    <option value="" disabled selected>Selecciona un servicio</option>
                    @foreach($services as $service)
                        <option value="{{ $service->id }}">{{ $service->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Fecha y Hora -->
            <div>
                <label for="fecha_hora" class="block text-sm font-bold text-slate-700 mb-1">Fecha y Hora</label>
                <input type="datetime-local" name="fecha_hora" id="fecha_hora" required class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-900 focus:ring-blue-900">
            </div>

            <!-- Motivo de la consulta -->
            <div>
                <label for="motivo" class="block text-sm font-bold text-slate-700 mb-1">Motivo de la consulta (Opcional)</label>
                <textarea name="motivo" id="motivo" rows="3" placeholder="Describe brevemente tus síntomas o razón de la consulta..." class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-900 focus:ring-blue-900"></textarea>
            </div>

            <!-- Botones -->
            <div class="flex justify-end gap-3 pt-4">
                <a href="{{ route('appointments.index') }}" class="px-5 py-2.5 rounded-xl border border-slate-300 text-slate-600 font-bold hover:bg-slate-50 transition">
                    Cancelar
                </a>
                <button type="submit" class="px-6 py-2.5 bg-blue-900 hover:bg-blue-800 text-white font-bold rounded-xl transition shadow">
                    Confirmar Cita
                </button>
            </div>
        </form>
    </div>
</x-layout>
