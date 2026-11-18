<x-layout>
    <div class="max-w-6xl mx-auto space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-blue-900">Cuentas de Doctores</h1>
                <p class="text-slate-600 text-sm mt-1">Consulta las cuentas con rol de doctor. Para cambiar el rol de un usuario, usa la sección Usuarios.</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100 text-slate-700 text-xs font-bold uppercase border-b border-slate-200">
                            <th class="py-3 px-4">Nombre</th>
                            <th class="py-3 px-4">Correo</th>
                            <th class="py-3 px-4">Carné</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-sm">
                        @forelse($doctors as $doctor)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="py-3 px-4 font-bold text-slate-800">{{ $doctor->name }}</td>
                                <td class="py-3 px-4 text-slate-600">{{ $doctor->email }}</td>
                                <td class="py-3 px-4 font-mono text-slate-600">{{ $doctor->carnet ?? 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-8 px-4 text-center text-slate-500">Aún no hay cuentas de doctor registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layout>
