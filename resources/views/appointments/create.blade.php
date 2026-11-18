<x-layout>
    <div class="max-w-2xl mx-auto bg-white p-8 rounded-2xl shadow-sm border border-slate-200 mt-4">
        <h1 class="text-2xl font-extrabold text-blue-900 mb-2">Agendar Cita Médica</h1>
        <p class="text-slate-600 text-sm mb-6">Completa el formulario para solicitar tu atención médica en la ULS. Si el doctor está atendiendo otra consulta, tu solicitud quedará pendiente hasta que esté disponible.</p>

        <form action="{{ route('appointments.store') }}" method="POST" class="space-y-5">
            @csrf

            <!-- Selección de Servicio -->
            <div>
                <label for="service_id" class="block text-sm font-bold text-slate-700 mb-1">Servicio médico obligatorio</label>
                <select name="service_id" id="service_id" required class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-900 focus:ring-blue-900">
                    <option value="" disabled {{ old('service_id') ? '' : 'selected' }}>Selecciona un servicio</option>
                    @foreach($services as $service)
                        @php
                            $doctor = $service->doctors->first();
                            $isDoctorAvailable = $doctor !== null && (int) $doctor->active_appointments_count === 0;
                        @endphp
                        <option
                            value="{{ $service->id }}"
                            data-doctor-name="{{ $doctor->name ?? '' }}"
                            data-doctor-available="{{ $isDoctorAvailable ? 'true' : 'false' }}"
                            {{ old('service_id') == $service->id ? 'selected' : '' }}
                        >{{ $service->nombre }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('service_id')" class="mt-2" />
                <p id="doctor-availability" role="status" aria-live="polite" class="mt-3 rounded-xl bg-slate-100 p-3 text-sm text-slate-600">
                    Selecciona un servicio para consultar la disponibilidad del doctor.
                </p>
            </div>

            <!-- Fecha y Hora -->
            <div>
                <label for="fecha_hora" class="block text-sm font-bold text-slate-700 mb-1">Fecha y Hora</label>
                <input type="datetime-local" name="fecha_hora" id="fecha_hora" value="{{ old('fecha_hora') }}" min="{{ now()->format('Y-m-d\TH:i') }}" required class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-900 focus:ring-blue-900">
                <x-input-error :messages="$errors->get('fecha_hora')" class="mt-2" />
            </div>

            <!-- Motivo de la consulta -->
            <div>
                <label for="motivo" class="block text-sm font-bold text-slate-700 mb-1">Motivo de la consulta (Opcional)</label>
                <textarea name="motivo" id="motivo" rows="3" placeholder="Describe brevemente tus síntomas o razón de la consulta..." class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-900 focus:ring-blue-900">{{ old('motivo') }}</textarea>
                <x-input-error :messages="$errors->get('motivo')" class="mt-2" />
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

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const serviceSelect = document.getElementById('service_id');
            const availabilityMessage = document.getElementById('doctor-availability');

            const updateAvailability = () => {
                const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];

                if (!selectedOption || !selectedOption.value) {
                    availabilityMessage.textContent = 'Selecciona un servicio para consultar la disponibilidad del doctor.';
                    availabilityMessage.className = 'mt-3 rounded-xl bg-slate-100 p-3 text-sm text-slate-600';
                    return;
                }

                const doctorName = selectedOption.dataset.doctorName;

                if (!doctorName) {
                    availabilityMessage.textContent = 'No hay un doctor asignado a este servicio por el momento.';
                    availabilityMessage.className = 'mt-3 rounded-xl bg-rose-100 p-3 text-sm font-semibold text-rose-800';
                    return;
                }

                if (selectedOption.dataset.doctorAvailable === 'true') {
                    availabilityMessage.textContent = `${doctorName} está disponible para atender una consulta.`;
                    availabilityMessage.className = 'mt-3 rounded-xl bg-emerald-100 p-3 text-sm font-semibold text-emerald-800';
                    return;
                }

                availabilityMessage.textContent = `${doctorName} no está disponible porque tiene una consulta pendiente o en curso. Puedes enviar tu solicitud y quedará pendiente hasta que esté disponible.`;
                availabilityMessage.className = 'mt-3 rounded-xl bg-amber-100 p-3 text-sm font-semibold text-amber-800';
            };

            serviceSelect.addEventListener('change', updateAvailability);
            updateAvailability();
        });
    </script>
</x-layout>
