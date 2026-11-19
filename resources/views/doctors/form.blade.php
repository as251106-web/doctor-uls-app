<x-layout>
    @php
        $isEditingDoctor = $editingDoctor !== null;
        $selectedServiceId = old('service_id', $editingDoctor?->services->first()?->id);
    @endphp

    <div class="mx-auto max-w-3xl space-y-6">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-extrabold text-blue-900">
                    {{ $isEditingDoctor ? 'Editar doctor' : 'Registrar nuevo doctor' }}
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    {{ $isEditingDoctor ? 'Actualiza los datos y la especialización asignada.' : 'La cuenta se creará directamente con el rol de doctor.' }}
                </p>
            </div>
            <a href="{{ route('doctors.index') }}" class="font-bold text-blue-900 hover:underline">
                Volver a doctores
            </a>
        </header>

        @if($errors->any())
            <div class="rounded-xl border-l-4 border-rose-500 bg-rose-100 p-4 text-rose-800">
                <ul class="list-inside list-disc space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($services->isEmpty())
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                No hay especializaciones activas disponibles sin doctor asignado. Crea una en Servicios o libera una existente antes de continuar.
            </div>
        @endif

        <section class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <form
                action="{{ $isEditingDoctor ? route('doctors.update', $editingDoctor) : route('doctors.store') }}"
                method="POST"
                class="space-y-5"
            >
                @csrf
                @if($isEditingDoctor)
                    @method('PATCH')
                @endif

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="name" class="mb-1 block text-sm font-bold text-slate-700">Nombre completo</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $editingDoctor?->name) }}" required maxlength="255" autocomplete="name" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-900 focus:ring-blue-900">
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <label for="email" class="mb-1 block text-sm font-bold text-slate-700">Correo electrónico</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $editingDoctor?->email) }}" required maxlength="255" autocomplete="email" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-900 focus:ring-blue-900">
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <label for="carnet" class="mb-1 block text-sm font-bold text-slate-700">Carné</label>
                        <input id="carnet" name="carnet" type="text" value="{{ old('carnet', $editingDoctor?->carnet) }}" required maxlength="20" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-900 focus:ring-blue-900">
                        <x-input-error :messages="$errors->get('carnet')" class="mt-2" />
                    </div>

                    <div>
                        <label for="service_id" class="mb-1 block text-sm font-bold text-slate-700">Especialización</label>
                        <select id="service_id" name="service_id" required {{ $services->isEmpty() ? 'disabled' : '' }} class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 disabled:cursor-not-allowed disabled:bg-slate-100">
                            <option value="" disabled {{ $selectedServiceId ? '' : 'selected' }}>Selecciona una especialización</option>
                            @foreach($services as $service)
                                <option value="{{ $service->id }}" {{ (string) $selectedServiceId === (string) $service->id ? 'selected' : '' }}>
                                    {{ $service->nombre }}{{ !$service->activo ? ' (Inactiva, asignación actual)' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('service_id')" class="mt-2" />
                    </div>

                    <div>
                        <label for="password" class="mb-1 block text-sm font-bold text-slate-700">
                            {{ $isEditingDoctor ? 'Nueva contraseña (opcional)' : 'Contraseña' }}
                        </label>
                        <input id="password" name="password" type="password" {{ $isEditingDoctor ? '' : 'required' }} autocomplete="new-password" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-900 focus:ring-blue-900">
                        @if($isEditingDoctor)
                            <p class="mt-1 text-xs text-slate-500">Déjala vacía para conservar la contraseña actual.</p>
                        @endif
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <label for="password_confirmation" class="mb-1 block text-sm font-bold text-slate-700">Confirmar contraseña</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" {{ $isEditingDoctor ? '' : 'required' }} autocomplete="new-password" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-900 focus:ring-blue-900">
                    </div>
                </div>

                <div class="flex justify-end border-t border-slate-100 pt-4">
                    <button type="submit" {{ $services->isEmpty() ? 'disabled' : '' }} class="rounded-xl bg-blue-900 px-5 py-2.5 font-bold text-white shadow transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50">
                        {{ $isEditingDoctor ? 'Guardar cambios' : 'Registrar doctor' }}
                    </button>
                </div>
            </form>
        </section>
    </div>
</x-layout>
