<nav x-data="{ open: false }" @click.outside="open = false" class="bg-white/90 backdrop-blur-md border-b border-gray-200/50 sticky top-0 z-50 shadow-sm">
    <!-- Primary Navigation Menu -->
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex min-w-0">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-12 w-auto" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-2 xl:-my-px xl:ms-10 xl:flex items-center flex-nowrap min-w-0">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    @can('view-orders')
                        <x-nav-link :href="route('orders.index')" :active="request()->routeIs('orders.*')">
                            {{ __('Órdenes') }}
                        </x-nav-link>
                    @endcan

                    @can('view-clients')
                        <x-nav-link :href="route('clients.index')" :active="request()->routeIs('clients.*')">
                            {{ __('Clientes') }}
                        </x-nav-link>
                    @endcan

                    @can('view-users')
                        <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                            {{ __('Usuarios') }}
                        </x-nav-link>
                    @endcan

                    @can('view-materials')
                        <div class="hidden xl:flex xl:items-center xl:ms-4">
                            <x-dropdown align="left" width="48">
                                <x-slot name="trigger">
                                    <button
                                        class="inline-flex items-center justify-center px-4 py-1.5 rounded-full text-sm font-medium transition duration-150 ease-in-out {{ request()->routeIs('materials.*') ? 'bg-indigo-50 text-indigo-700 border border-indigo-100 shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50 border border-transparent' }}">
                                        <div class="max-w-[100px] truncate text-center">{{ __('Materiales') }}</div>

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
                                    <x-dropdown-link :href="route('materials.index')" :active="request()->routeIs('materials.index')">
                                        {{ __('Inventario') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="route('materials.consumption')" :active="request()->routeIs('materials.consumption')">
                                        {{ __('Consumo') }}
                                    </x-dropdown-link>
                                </x-slot>
                            </x-dropdown>
                        </div>
                    @endcan

                    @can('view-bodega')
                        <x-nav-link :href="route('bodega.index')" :active="request()->routeIs('bodega.*')">
                            {{ __('Bodega') }}
                        </x-nav-link>
                    @endcan

                    @can('view-special-services')
                        <x-nav-link :href="route('special-services.index')"
                            :active="request()->routeIs('special-services.*')">
                            {{ __('Servicios Esp.') }}
                        </x-nav-link>
                    @endcan

                    @can('view-performance')
                        <x-nav-link :href="route('performance.index')" :active="request()->routeIs('performance.*')">
                            {{ __('Rendimiento') }}
                        </x-nav-link>
                    @endcan
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden xl:flex xl:items-center xl:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button
                            class="inline-flex items-center justify-center px-3 py-1 border border-gray-200 rounded-full text-sm font-medium text-gray-600 bg-gray-50 hover:bg-white hover:shadow-sm transition ease-in-out duration-150">
                            <div class="w-7 h-7 shrink-0 bg-indigo-100 rounded-full flex items-center justify-center text-xs text-indigo-700 font-bold me-2">
                                {{ substr(Auth::user()->name, 0, 1) }}
                            </div>
                            <div class="max-w-[80px] truncate text-center">{{ Auth::user()->name }}</div>

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

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Cerrar sesión') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center xl:hidden">
                <button @click="open = ! open"
                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute top-16 right-4 w-72 max-w-[calc(100vw-2rem)] max-h-[calc(100vh-5rem)] bg-white shadow-2xl rounded-2xl border border-gray-100 z-50 overflow-y-auto xl:hidden"
         style="display: none;">
        <div class="pt-4 pb-4 px-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            @can('view-orders')
                <x-responsive-nav-link :href="route('orders.index')" :active="request()->routeIs('orders.*')">
                    {{ __('Órdenes') }}
                </x-responsive-nav-link>
            @endcan

            @can('view-clients')
                <x-responsive-nav-link :href="route('clients.index')" :active="request()->routeIs('clients.*')">
                    {{ __('Clientes') }}
                </x-responsive-nav-link>
            @endcan

            @can('view-users')
                <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                    {{ __('Usuarios') }}
                </x-responsive-nav-link>
            @endcan

            @can('view-materials')
                <div class="pt-3 pb-2 mt-4 bg-white rounded-xl shadow-sm border border-gray-100 mx-1">
                    <div class="px-4 py-1 text-xs font-bold text-indigo-400 uppercase tracking-wider mb-2">
                        <i class="bi bi-box-seam me-1"></i> {{ __('Materiales') }}
                    </div>
                    <div class="px-2 space-y-1">
                        <x-responsive-nav-link :href="route('materials.index')" :active="request()->routeIs('materials.index')">
                            {{ __('Inventario') }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('materials.consumption')" :active="request()->routeIs('materials.consumption')">
                            {{ __('Consumo') }}
                        </x-responsive-nav-link>
                    </div>
                </div>
            @endcan

            @can('view-bodega')
                <x-responsive-nav-link :href="route('bodega.index')" :active="request()->routeIs('bodega.*')">
                    {{ __('Bodega') }}
                </x-responsive-nav-link>
            @endcan

            @can('view-special-services')
                <x-responsive-nav-link :href="route('special-services.index')"
                    :active="request()->routeIs('special-services.*')">
                    {{ __('Servicios Esp.') }}
                </x-responsive-nav-link>
            @endcan

            @can('view-performance')
                <x-responsive-nav-link :href="route('performance.index')" :active="request()->routeIs('performance.*')">
                    {{ __('Rendimiento') }}
                </x-responsive-nav-link>
            @endcan
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-5 pb-5 border-t border-gray-200 bg-white mt-2">
            <div class="px-4 flex items-center gap-3">
                <div class="flex-shrink-0 w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-700 font-bold text-lg">
                    {{ substr(Auth::user()->name, 0, 1) }}
                </div>
                <div class="min-w-0">
                    <div class="font-bold text-base text-gray-800 truncate">{{ Auth::user()->name }}</div>
                    <div class="font-medium text-sm text-gray-500 truncate">{{ Auth::user()->document }}</div>
                </div>
            </div>

            <div class="mt-4 px-3 space-y-1">
                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault();
                                        this.closest('form').submit();" class="text-red-600 hover:bg-red-50 hover:border-red-100 hover:text-red-700">
                        <i class="bi bi-box-arrow-right me-2"></i> {{ __('Cerrar sesión') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>