@php
    $user = Auth::user();
    $isAdmin = $user->isAdmin();
    $homeRoute = 'home';
    $appointmentsLabel = $user->isCliente() ? 'Mis citas' : 'Citas médicas';
@endphp

<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex min-h-16 flex-wrap items-center justify-between gap-4 py-2">
            <div class="flex flex-wrap items-center gap-4">
                <a href="{{ route($homeRoute) }}" class="shrink-0">
                    <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                </a>

                <div class="hidden flex-wrap items-center gap-5 sm:flex">
                    @if(!$isAdmin && !$user->isDoctor())
                        <x-nav-link :href="route('home')" :active="request()->routeIs('home')">
                            Inicio
                        </x-nav-link>
                    @endif

                    @if(!$user->isDoctor())
                        <x-nav-link :href="route('appointments.index')" :active="request()->routeIs('appointments.*')">
                            {{ $appointmentsLabel }}
                        </x-nav-link>
                    @endif

                    @if($user->isCliente())
                        <x-nav-link :href="route('medical-history.index')" :active="request()->routeIs('medical-history.*')">
                            Historial médico
                        </x-nav-link>
                    @endif

                    @if($isAdmin)
                        <x-nav-link :href="route('services.index')" :active="request()->routeIs('services.*')">
                            Servicios
                        </x-nav-link>
                        <x-nav-link :href="route('doctors.index')" :active="request()->routeIs('doctors.*')">
                            Doctores
                        </x-nav-link>
                        <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                            Usuarios
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:gap-4">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 rounded-md border border-transparent bg-white px-3 py-2 text-sm leading-4 font-medium text-gray-500 transition hover:text-gray-700 focus:outline-none">
                            <span>{{ $user->name }}</span>
                            <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            Perfil
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                Cerrar sesión
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="space-y-1 px-4 pt-2 pb-3">
            @if(!$isAdmin && !$user->isDoctor())
                <x-responsive-nav-link :href="route('home')" :active="request()->routeIs('home')">
                    Inicio
                </x-responsive-nav-link>
            @endif

            @if(!$user->isDoctor())
                <x-responsive-nav-link :href="route('appointments.index')" :active="request()->routeIs('appointments.*')">
                    {{ $appointmentsLabel }}
                </x-responsive-nav-link>
            @endif

            @if($user->isCliente())
                <x-responsive-nav-link :href="route('medical-history.index')" :active="request()->routeIs('medical-history.*')">
                    Historial médico
                </x-responsive-nav-link>
            @endif

            @if($isAdmin)
                <x-responsive-nav-link :href="route('services.index')" :active="request()->routeIs('services.*')">
                    Servicios
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('doctors.index')" :active="request()->routeIs('doctors.*')">
                    Doctores
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                    Usuarios
                </x-responsive-nav-link>
            @endif
        </div>

        <div class="border-t border-gray-200 px-4 pt-4 pb-1">
            <div>
                <div class="text-base font-medium text-gray-800">{{ $user->name }}</div>
                <div class="text-sm font-medium text-gray-500">{{ $user->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    Perfil
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                        Cerrar sesión
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
