<nav x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ url('/') }}">
                        <img src="{{ Vite::asset('resources/images/ime/logo-horizontal-simplificada-padrao.png') }}" alt="Logo IME-USP" class="w-20 h-auto block dark:hidden" dusk="ime-logo-light">
                        <img src="{{ Vite::asset('resources/images/ime/logo-horizontal-simplificada-branca.png') }}" alt="Logo IME-USP" class="w-20 h-auto hidden dark:block" dusk="ime-logo-dark">
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="url('/')" :active="request()->is('/')">
                        {{ __('Home') }}
                    </x-nav-link>
                    <x-nav-link :href="route('workshops')" :active="request()->routeIs('workshops')">
                        {{ __('Workshops') }}
                    </x-nav-link>
                    <x-nav-link :href="route('fees')" :active="request()->routeIs('fees')">
                        {{ __('Fees') }}
                    </x-nav-link>
                    <x-nav-link :href="route('payment-info')" :active="request()->routeIs('payment-info')">
                        {{ __('Payment') }}
                    </x-nav-link>
                    @auth
                        <x-nav-link :href="route('registrations.my')" :active="request()->routeIs('registrations.my')">
                            {{ __('My Registrations') }}
                        </x-nav-link>
                    @endauth
                </div>
            </div>

            <!-- Authentication Links -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                @guest
                    <div class="flex space-x-4">
                        <x-nav-link :href="route('login.local')" :active="request()->routeIs('login.local')">
                            {{ __('Login') }}
                        </x-nav-link>
                    </div>
                @endguest
                @auth
                    <div class="flex space-x-4 mr-4">
                        <x-nav-link :href="route('register-event')" :active="request()->routeIs('register-event')">
                            {{ __('Sign Up') }}
                        </x-nav-link>
                    </div>
                    <!-- Settings Dropdown -->
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150">
                                <div>{{ auth()->user()->name }}</div>
                                <div class="ms-1">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('dashboard')">
                                {{ __('Dashboard') }}
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('profile')">
                                {{ __('Profile') }}
                            </x-dropdown-link>

                            @if(auth()->user() && auth()->user()->hasRole('admin'))
                                <x-dropdown-link :href="route('admin.registrations.index')">
                                    {{ __('Registrations') }}
                                </x-dropdown-link>
                            @endif

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}" class="w-full">
                                @csrf
                                <button type="submit" class="w-full text-start block px-4 py-2 text-sm leading-5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-800 transition duration-150 ease-in-out">
                                    {{ __('Log Out') }}
                                </button>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @endauth
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="url('/')" :active="request()->is('/')">
                {{ __('Home') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('workshops')" :active="request()->routeIs('workshops')">
                {{ __('Workshops') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('fees')" :active="request()->routeIs('fees')">
                {{ __('Fees') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('payment-info')" :active="request()->routeIs('payment-info')">
                {{ __('Payment') }}
            </x-responsive-nav-link>
            @auth
                <x-responsive-nav-link :href="route('registrations.my')" :active="request()->routeIs('registrations.my')">
                    {{ __('My Registrations') }}
                </x-responsive-nav-link>
            @endauth
        </div>

        <!-- Responsive Authentication Links -->
        @guest
            <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('login.local')" :active="request()->routeIs('login.local')">
                        {{ __('Login') }}
                    </x-responsive-nav-link>
                </div>
            </div>
        @endguest
        @auth
            <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
                <div class="px-4">
                    <div class="font-medium text-base text-gray-800 dark:text-gray-200">{{ auth()->user()->name }}</div>
                    <div class="font-medium text-sm text-gray-500">{{ auth()->user()->email }}</div>
                </div>

                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('register-event')" :active="request()->routeIs('register-event')">
                        {{ __('Sign Up') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('dashboard')">
                        {{ __('Dashboard') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('registrations.my')" :active="request()->routeIs('registrations.my')">
                        {{ __('My Registrations') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('profile')">
                        {{ __('Profile') }}
                    </x-responsive-nav-link>
                    @if(auth()->user() && auth()->user()->hasRole('admin'))
                        <x-responsive-nav-link :href="route('admin.registrations.index')">
                            {{ __('Registrations') }}
                        </x-responsive-nav-link>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <button type="submit" class="w-full text-start block px-4 py-2 border-l-4 border-transparent text-base font-medium text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-gray-300 dark:hover:border-gray-600 focus:outline-none focus:text-gray-800 dark:focus:text-gray-200 focus:bg-gray-50 dark:focus:bg-gray-700 focus:border-gray-300 dark:focus:border-gray-600 transition duration-150 ease-in-out">
                            {{ __('Log Out') }}
                        </button>
                    </form>
                </div>
            </div>
        @endauth
    </div>
</nav>