<x-layout>
    <div class="mx-auto max-w-6xl space-y-8">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-extrabold text-blue-900">Cuentas de Doctores</h1>
                <p class="mt-1 text-sm text-slate-600">Registra doctores y asígnales una especialización existente.</p>
            </div>
            <a href="{{ route('doctors.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-blue-900 px-5 py-2.5 font-bold text-white shadow transition hover:bg-blue-800">
                <span>➕</span>
                Registrar nuevo doctor
            </a>
        </header>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-5">
                <h2 class="text-xl font-bold text-blue-900">Doctores registrados</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-100 text-xs font-bold uppercase text-slate-700">
                            <th class="px-4 py-3">Nombre</th>
                            <th class="px-4 py-3">Correo</th>
                            <th class="px-4 py-3">Carné</th>
                            <th class="px-4 py-3">Especialización</th>
                            <th class="px-4 py-3 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-sm">
                        @forelse($doctors as $doctor)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-4 py-3 font-bold text-slate-800">{{ $doctor->name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $doctor->email }}</td>
                                <td class="px-4 py-3 font-mono text-slate-600">{{ $doctor->carnet ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $doctor->services->first()?->nombre ?? 'Sin especialización' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('doctors.edit', $doctor) }}" class="font-bold text-blue-900 hover:underline">
                                        Editar
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-slate-500">Aún no hay cuentas de doctor registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layout>
