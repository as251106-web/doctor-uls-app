<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor ULS - Atención Médica Universitaria</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased min-h-screen flex flex-col">
    <!-- Encabezado con Identidad ULS -->
    <nav class="bg-blue-900 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <a href="{{ route('home') }}" class="text-2xl font-black tracking-wider flex items-center gap-2">
                <span>🩺</span> DOCTOR ULS
            </a>
            <div class="space-x-4 flex items-center text-sm font-semibold">
                <a href="{{ route('home') }}" class="hover:text-blue-200">Inicio</a>

                @auth
                    <a href="{{ route('appointments.index') }}" class="hover:text-blue-200">Mis Citas Médicas</a>

                    @if(Auth::user()->isAdmin() || Auth::user()->isDoctor())
                        <a href="{{ route('services.index') }}" class="hover:text-blue-200">Servicios</a>
                    @endif

                    @if(Auth::user()->isAdmin())
                        <a href="{{ route('users.index') }}" class="hover:text-blue-200">Usuarios</a>
                    @endif

                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="bg-red-600 hover:bg-red-700 px-3 py-1.5 rounded-md text-white transition font-bold">Cerrar Sesión</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="hover:text-blue-200">Ingresar</a>
                    <a href="{{ route('register') }}" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-md transition text-white">Registrarse</a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Contenido dinámico -->
    <main class="flex-grow container mx-auto px-4 py-8">
        @if(session('success'))
            <div class="bg-emerald-100 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded-xl mb-6 shadow-sm font-medium">
                {{ session('success') }}
            </div>
        @endif

        {{ $slot }}
    </main>

    <!-- Pie de página -->
    <footer class="bg-slate-900 text-slate-400 text-center py-6 text-sm border-t border-slate-800">
        <p>&copy; {{ date('Y') }} Doctor ULS — Universidad Luterana Salvadoreña</p>
        <p class="text-xs text-slate-500 mt-1">Proyecto de Aplicaciones Web — Licenciatura en Ciencias de la Computación</p>
    </footer>
</body>
</html>
