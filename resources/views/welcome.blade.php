<x-layout>
    <!-- Banner Principal -->
    <div class="text-center py-12 bg-white rounded-2xl shadow-sm border border-slate-200 mt-4 mb-10 px-6">

        <!-- Logo de la ULS centrado arriba del título -->
        <div class="flex justify-center mb-4">
            <img src="{{ asset('images/logo-uls.png') }}" alt="Logo ULS" class="h-40 w-auto object-contain">
        </div>
        <h1 class="text-3xl font-bold text-slate-900 mb-2">Bienvenido a Doctor ULS</h1>

        @guest
            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 bg-blue-900 text-white px-8 py-3.5 rounded-xl font-bold hover:bg-blue-800 transition shadow-md">
                <span>📝</span> Registrarme con mi Carné ULS
            </a>
        @else
            <a href="{{ route('appointments.create') }}" class="inline-flex items-center gap-2 bg-blue-900 text-white px-8 py-3.5 rounded-xl font-bold hover:bg-blue-800 transition shadow-md">
                <span>📅</span> Agendar Cita Médica
            </a>
        @endguest
    </div>

    <!-- Sección de Servicios Médicos -->
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
            <span>📋</span> Servicios Médicos Disponibles
        </h2>
        <span class="text-xs text-slate-800">Atención presencial en campus</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach($services as $service)
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition flex flex-col justify-between">
                <div>
                    <div class="w-12 h-12 bg-blue-50 text-blue-900 rounded-xl flex items-center justify-center text-2xl mb-4 font-bold">
                        @if(Str::contains(Str::lower($service->nombre), 'general'))
                            🩺
                        @elseif(Str::contains(Str::lower($service->nombre), 'auxilio'))
                            🩹
                        @elseif(Str::contains(Str::lower($service->nombre), 'mental'))
                            🧠
                        @else
                            🏥
                        @endif
                    </div>

                    <h3 class="text-lg font-bold text-blue-900 mb-2">{{ $service->nombre }}</h3>
                    <p class="text-slate-600 text-sm mb-6 leading-relaxed">{{ $service->descripcion }}</p>
                </div>

                <div>
                    <span class="inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-700 text-xs px-3 py-1 rounded-full font-semibold border border-emerald-200">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Atención Activa
                    </span>
                </div>
            </div>
        @endforeach
    </div>
</x-layout>
