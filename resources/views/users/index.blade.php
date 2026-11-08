<x-layout>
    <div class="max-w-6xl mx-auto space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-extrabold text-blue-900">Gestión de Usuarios y Roles</h1>
                <p class="text-slate-600 text-sm mt-1">Administración de cuentas registradas en la plataforma Doctor ULS.</p>
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
                            <th class="py-3 px-4">Rol Actual</th>
                            <th class="py-3 px-4 text-right">Cambiar Rol</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-sm">
                        @foreach($users as $u)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="py-3 px-4 font-bold text-slate-800">{{ $u->name }}</td>
                                <td class="py-3 px-4 text-slate-600">{{ $u->email }}</td>
                                <td class="py-3 px-4 font-mono text-slate-600">{{ $u->carnet ?? 'N/A' }}</td>
                                <td class="py-3 px-4">
                                    <span class="inline-block px-2.5 py-1 text-xs font-bold rounded-full {{ $u->isAdmin() ? 'bg-purple-100 text-purple-800' : ($u->isDoctor() ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-700') }}">
                                        {{ ucfirst($u->role->nombre ?? 'Sin Rol') }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <form action="{{ route('users.updateRole', $u) }}" method="POST" class="inline-flex items-center gap-1">
                                        @csrf
                                        @method('PATCH')
                                        <select name="role_id" onchange="this.form.submit()" class="text-xs rounded-lg border-slate-300 py-1 px-2 focus:ring-blue-900 font-semibold bg-white shadow-sm">
                                            @foreach($roles as $role)
                                                <option value="{{ $role->id }}" {{ $u->role_id == $role->id ? 'selected' : '' }}>
                                                    {{ ucfirst($role->nombre) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layout>
