<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links - Desktop -->
                <div class="hidden lg:flex lg:space-x-8 lg:ms-10">
                    {{-- Dashboard - Siempre visible --}}
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    {{-- WMS - visible si tiene algún permiso de WMS --}}
                    @hasanyrole('WMS-ADMIN|WMS-ALMACEN|WMS-MONTACARGA|SIS-ADMIN')
                        <x-nav-link :href="route('wms.index')" :active="request()->routeIs('wms.*')">
                            WMS
                        </x-nav-link>
                    @endhasanyrole

                    {{-- Solo SIS-ADMIN --}}
                    @can('sis.verificar-bd')
                        <x-nav-link :href="route('verificar-bd.index')" :active="request()->routeIs('verificar-bd.*')">
                            {{ __('Verificar estado BD') }}
                        </x-nav-link>
                    @endcan

                    @can('sis.migrar-contables')
                        <x-nav-link :href="route('migrar.contables.index')" :active="request()->routeIs('migrar.contables.*')">
                            Migrar Datos Contables
                        </x-nav-link>
                    @endcan

                    @can('sis.migrar-inv')
                        <x-nav-link :href="route('migrar.inv.index')" :active="request()->routeIs('migrar.inv.*')">
                            Migrar Datos Inv
                        </x-nav-link>
                    @endcan

                    <!-- BIOMÉTRICO Dropdown - visible para RRHH-ADMIN, RRHH-USER o SIS-ADMIN -->
                    @hasanyrole('RRHH-ADMIN|RRHH-USER|SIS-ADMIN')
                        <div x-data="{ openRrhh: false, openBio: false, openAsist: false }" class="relative">

                            {{-- Menú principal RRHH --}}
                            <button @click="openRrhh = !openRrhh"
                                class="flex items-center gap-1 text-sm font-medium text-gray-600
                   hover:text-gray-900 focus:outline-none">
                                RRHH
                                <svg class="w-4 h-4 transition-transform" :class="openRrhh ? 'rotate-180' : ''"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div x-show="openRrhh" @click.away="openRrhh = false"
                                class="absolute left-0 mt-2 w-56 bg-white rounded-md shadow-lg
                ring-1 ring-black ring-opacity-5 z-50">

                                {{-- Sub-menú Biométricos --}}
                                <div x-data="{ openBio: false }" class="relative">
                                    <button @click="openBio = !openBio"
                                        class="w-full flex items-center justify-between px-4 py-2
                           text-sm text-gray-700 hover:bg-gray-100">
                                        <span>Biométricos</span>
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                    <div x-show="openBio"
                                        class="absolute left-full top-0 mt-0 ml-1 w-52 bg-white
                        rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50">
                                        <a href="{{ route('biometricos.recuperar') }}"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            Recuperar Datos
                                        </a>
                                        <a href="{{ route('biometricos.importar') }}"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            Importar desde USB
                                        </a>
                                        <a href="{{ route('biometricos.reporte') }}"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            Reporte Asistencia
                                        </a>
                                        <a href="{{ route('biometricos.novedades') }}"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            Registro de Novedades
                                        </a>
                                    </div>
                                </div>

                                {{-- Sub-menú Asistencia --}}
                                <div x-data="{ openAsist: false }" class="relative">
                                    <button @click="openAsist = !openAsist"
                                        class="w-full flex items-center justify-between px-4 py-2
                           text-sm text-gray-700 hover:bg-gray-100">
                                        <span>Asistencia</span>
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                    <div x-show="openAsist"
                                        class="absolute left-full top-0 mt-0 ml-1 w-52 bg-white
                        rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50">
                                        <a href="{{ route('asistencia.registro') }}"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            Registro de Asistencia
                                        </a>
                                        <a href="{{ route('asistencia.mis-marcajes') }}"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            Mis Marcajes
                                        </a>
                                        <a href="{{ route('asistencia.reporte') }}"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            Reporte Asistencia APP
                                        </a>
                                        <a href="{{ route('rrhh.agencias.asignaciones') }}"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            Asignación por Usuario
                                        </a>
                                    </div>
                                </div>

                                {{-- Sub-menú Agencias --}}
                                <div x-data="{ openAg: false }" class="relative">
                                    <button @click="openAg = !openAg"
                                        class="w-full flex items-center justify-between px-4 py-2
                   text-sm text-gray-700 hover:bg-gray-100">
                                        <span>Agencias</span>
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                    <div x-show="openAg"
                                        class="absolute left-full top-0 mt-0 ml-1 w-52 bg-white
                rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50">
                                        <a href="{{ route('rrhh.agencias.index') }}"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            Lista de Agencias
                                        </a>
                                        <a href="{{ route('rrhh.agencias.create') }}"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            Nueva Agencia
                                        </a>
                                        <a href="{{ route('rrhh.agencias.asignaciones') }}"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            Asignación por Usuario
                                        </a>
                                    </div>
                                </div>

                            </div>
                        </div>
                    @endhasanyrole

                    <!-- Comercial Dropdown - solo SIS-ADMIN -->
                    @hasanyrole('SIS-ADMIN')
                        <div class="hidden lg:flex lg:items-center">
                            <x-dropdown align="right" width="48">
                                <x-slot name="trigger">
                                    <button
                                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                        <div>Comercial</div>
                                        <div class="ms-1">
                                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </button>
                                </x-slot>

                                <x-slot name="content">
                                    @can('sis.comercial.agencias')
                                        <x-dropdown-link :href="route('comercial.agencias.index')">Agencias</x-dropdown-link>
                                    @endcan
                                    @can('sis.comercial.contactos')
                                        <x-dropdown-link :href="route('comercial.contactos.index')">Contactos</x-dropdown-link>
                                    @endcan
                                </x-slot>
                            </x-dropdown>
                        </div>
                    @endhasanyrole
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden lg:flex lg:items-center lg:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button
                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        @role('SIS-ADMIN')
                            <x-dropdown-link :href="route('admin.usuarios.index')">
                                {{ __('Usuarios y Roles') }}
                            </x-dropdown-link>
                        @endrole

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="flex items-center lg:hidden">
                <button @click="open = !open"
                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{ 'hidden': open, 'inline-flex': !open }" class="inline-flex"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{ 'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu - Mobile -->
    <div :class="{ 'block': open, 'hidden': !open }" class="hidden lg:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            @hasanyrole('WMS-ADMIN|WMS-ALMACEN|WMS-MONTACARGA|SIS-ADMIN')
                <x-responsive-nav-link :href="route('wms.index')" :active="request()->routeIs('wms.*')">
                    WMS
                </x-responsive-nav-link>
            @endhasanyrole

            @can('sis.verificar-bd')
                <x-responsive-nav-link :href="route('verificar-bd.index')" :active="request()->routeIs('verificar-bd.*')">
                    {{ __('Verificar estado BD') }}
                </x-responsive-nav-link>
            @endcan

            @can('sis.migrar-contables')
                <x-responsive-nav-link :href="route('migrar.contables.index')" :active="request()->routeIs('migrar.contables.*')">
                    Migrar Datos Contables
                </x-responsive-nav-link>
            @endcan

            @can('sis.migrar-inv')
                <x-responsive-nav-link :href="route('migrar.inv.index')" :active="request()->routeIs('migrar.inv.*')">
                    Migrar Datos Inv
                </x-responsive-nav-link>
            @endcan

            @hasanyrole('RRHH-ADMIN|RRHH-USER|SIS-ADMIN')
                <!-- Menú Biométricos Mobile -->
                <div x-data="{ openBiometrico: false }" class="px-2">
                    <button @click="openBiometrico = !openBiometrico"
                        class="flex items-center justify-between w-full py-2 text-sm font-medium text-gray-600 hover:text-gray-900 focus:outline-none">
                        <span>Biométricos</span>
                        <svg class="w-4 h-4 transition-transform" :class="openBiometrico ? 'rotate-180' : ''"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="openBiometrico" class="pl-4 space-y-1">
                        @can('rrhh.importar-usb')
                            <x-responsive-nav-link :href="route('biometricos.importar')"
                                class="block py-2 text-sm text-gray-600 hover:text-gray-900">
                                📥 Importar desde USB
                            </x-responsive-nav-link>
                        @endcan
                        @can('rrhh.recuperar-datos')
                            <x-responsive-nav-link :href="route('biometricos.recuperar')"
                                class="block py-2 text-sm text-gray-600 hover:text-gray-900">
                                📥 Recuperar Datos
                            </x-responsive-nav-link>
                        @endcan
                        @can('rrhh.reporte')
                            <x-responsive-nav-link :href="route('biometricos.reporte')"
                                class="block py-2 text-sm text-gray-600 hover:text-gray-900">
                                📊 Generar Reporte
                            </x-responsive-nav-link>
                        @endcan
                        @can('rrhh.novedades')
                            <x-responsive-nav-link :href="route('biometricos.novedades')"
                                class="block py-2 text-sm text-gray-600 hover:text-gray-900">
                                📊 Registro Novedades
                            </x-responsive-nav-link>
                        @endcan
                    </div>
                </div>
            @endhasanyrole

            @hasanyrole('SIS-ADMIN')
                <!-- Menú Comercial Mobile -->
                <div x-data="{ openComercial: false }" class="px-2">
                    <button @click="openComercial = !openComercial"
                        class="flex items-center justify-between w-full py-2 text-sm font-medium text-gray-600 hover:text-gray-900 focus:outline-none">
                        <span>Comercial</span>
                        <svg class="w-4 h-4 transition-transform" :class="openComercial ? 'rotate-180' : ''"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="openComercial" class="pl-4 space-y-1">
                        @can('sis.comercial.agencias')
                            <x-responsive-nav-link :href="route('comercial.agencias.index')"
                                class="block py-2 text-sm text-gray-600 hover:text-gray-900">
                                Agencias
                            </x-responsive-nav-link>
                        @endcan
                        @can('sis.comercial.contactos')
                            <x-responsive-nav-link :href="route('comercial.contactos.index')"
                                class="block py-2 text-sm text-gray-600 hover:text-gray-900">
                                Contactos
                            </x-responsive-nav-link>
                        @endcan
                    </div>
                </div>
            @endhasanyrole
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                @role('SIS-ADMIN')
                    <x-responsive-nav-link :href="route('admin.usuarios.index')">
                        {{ __('Usuarios y Roles') }}
                    </x-responsive-nav-link>
                @endrole

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                        onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
