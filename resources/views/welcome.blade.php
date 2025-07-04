<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ __('Welcome to 8th BCSMIF') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

        <!-- Scripts e Estilos via Vite -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased dark:bg-black dark:text-white/50">

        {{-- Cabeçalho USP --}}
        <x-usp.header />

        {{-- Navegação Pública --}}
        <x-layout.public-navigation />

        {{-- Container Geral --}}
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            
            {{-- Hero Section --}}
            <div class="bg-gradient-to-r from-blue-900 to-blue-700 text-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                    <div class="text-center">
                        <h1 class="text-4xl md:text-6xl font-bold mb-6">
                            {{ __('Welcome to 8th BCSMIF') }}
                        </h1>
                        <h2 class="text-xl md:text-2xl mb-8 font-light">
                            {{ __('8th Brazilian Conference on Statistical Modeling in Insurance and Finance') }}
                        </h2>
                        
                        <div class="flex flex-col md:flex-row gap-4 justify-center">
                            @guest
                                <a href="{{ route('login.local') }}" class="bg-white text-blue-900 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-300">
                                    {{ __('Login to Register') }}
                                </a>
                                <a href="{{ route('register') }}" class="bg-yellow-400 text-blue-900 px-8 py-3 rounded-lg font-semibold hover:bg-yellow-300 transition duration-300">
                                    {{ __('Create Account') }}
                                </a>
                            @endguest
                            <a href="https://8bcsmif.ime.usp.br/" target="_blank" class="bg-green-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-green-700 transition duration-300 text-center inline-flex items-center justify-center">
                                {{ __('Event Official Website') }}
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                            </a>
                            <a href="#details" class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-blue-900 transition duration-300">
                                {{ __('Learn More') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Main Content --}}
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                
                {{-- Conference Description --}}
                <section id="details" class="mb-16">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
                        <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-6">
                            {{ __('Conference Description') }}
                        </h2>
                        <p class="text-lg text-gray-700 dark:text-gray-300 leading-relaxed mb-8">
                            {{ __('The 8th BCSMIF objective is to provide a forum for presenting cutting-edge research on the development, implementation of recent methods in the field of Finance and Insurance, with emphasize on the practical applications of Data Science and Machine Learning.') }}
                        </p>
                        
                        {{-- Event Details Grid --}}
                        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">{{ __('Dates') }}</h3>
                                <p class="text-gray-700 dark:text-gray-300">{{ __('September 28 to October 3, 2025') }}</p>
                            </div>
                            <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">{{ __('Location') }}</h3>
                                <p class="text-gray-700 dark:text-gray-300">{{ __('Maresias Beach Hotel, Maresias, SP') }}</p>
                            </div>
                            <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">{{ __('Language') }}</h3>
                                <p class="text-gray-700 dark:text-gray-300">{{ __('English') }}</p>
                            </div>
                            <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">{{ __('Organizer') }}</h3>
                                <p class="text-gray-700 dark:text-gray-300 text-sm">{{ __('Institute of Mathematics and Statistics of the University of São Paulo (IME-USP)') }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Satellite Workshops --}}
                <section class="mb-16">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
                        <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-6">
                            {{ __('Satellite Workshops') }}
                        </h2>
                        <p class="text-lg text-gray-700 dark:text-gray-300 mb-8">
                            {{ __('Two satellite workshops will be held before 8th BCSMIF') }}:
                        </p>
                        
                        <div class="grid md:grid-cols-2 gap-8">
                            {{-- Workshop 1 --}}
                            <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-6">
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                                    {{ __('Workshop on Risk Analysis and Applications (WRAA)') }}
                                </h3>
                                <div class="space-y-2 text-gray-700 dark:text-gray-300 mb-4">
                                    <p><strong>{{ __('Dates') }}:</strong> {{ __('September 24+25, 2025') }}</p>
                                    <p><strong>{{ __('Location') }}:</strong> {{ __('At IME-USP, São Paulo') }}</p>
                                </div>
                                <a href="https://sites.google.com/usp.br/raa/" target="_blank" class="inline-flex items-center text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                                    {{ __('External Link') }}
                                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                    </svg>
                                </a>
                            </div>
                            
                            {{-- Workshop 2 --}}
                            <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-6">
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                                    {{ __('Workshop on Dependence Analysis (WDA)') }}
                                </h3>
                                <div class="space-y-2 text-gray-700 dark:text-gray-300 mb-4">
                                    <p><strong>{{ __('Dates') }}:</strong> {{ __('September 26+27, 2025') }}</p>
                                    <p><strong>{{ __('Location') }}:</strong> {{ __('At IMECC-UNICAMP, Campinas') }}</p>
                                </div>
                                <a href="https://sites.google.com/usp.br/wda-unicamp/" target="_blank" class="inline-flex items-center text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                                    {{ __('External Link') }}
                                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Quick Links --}}
                <section class="mb-16">
                    <div class="grid md:grid-cols-3 gap-8">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 text-center">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                                {{ __('Workshop Details') }}
                            </h3>
                            <p class="text-gray-700 dark:text-gray-300 mb-6">
                                {{ __('Get detailed information about each workshop, including topics, speakers, and registration details.') }}
                            </p>
                            <a href="/workshops" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition duration-300">
                                {{ __('View Workshop Details') }}
                            </a>
                        </div>
                        
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 text-center">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                                {{ __('Registration Fees') }}
                            </h3>
                            <p class="text-gray-700 dark:text-gray-300 mb-6">
                                {{ __('Complete fee information for all events and participant categories') }}
                            </p>
                            <a href="/fees" class="bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
                                {{ __('Check Registration Fees') }}
                            </a>
                        </div>
                        
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 text-center">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                                {{ __('Payment Information') }}
                            </h3>
                            <p class="text-gray-700 dark:text-gray-300 mb-6">
                                {{ __('Payment instructions and banking details') }}
                            </p>
                            <a href="/payment-info" class="bg-purple-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-purple-700 transition duration-300">
                                {{ __('Payment Instructions') }}
                            </a>
                        </div>
                    </div>
                </section>

                {{-- Registration Notice for Guests --}}
                @guest
                <section class="text-center mb-16">
                    <div class="bg-gradient-to-r from-orange-100 to-yellow-100 dark:from-orange-900 dark:to-yellow-900 rounded-lg shadow-lg p-8 border-2 border-orange-300 dark:border-orange-700">
                        <div class="flex justify-center mb-4">
                            <div class="bg-orange-500 text-white rounded-full p-3">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                        </div>
                        <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                            {{ __('Registration Requires Login') }}
                        </h2>
                        <p class="text-lg text-gray-700 dark:text-gray-300 mb-6 max-w-3xl mx-auto">
                            {{ __('To register for the 8th BCSMIF and satellite workshops, you must first login with your USP credentials (Senha Única) or create a new account. This ensures secure access to your registration information and payment status.') }}
                        </p>
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <a href="{{ route('login') }}" class="bg-blue-600 text-white px-8 py-4 rounded-lg font-semibold hover:bg-blue-700 transition duration-300 flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                                </svg>
                                {{ __('Login with Senha Única USP') }}
                            </a>
                            <a href="{{ route('register') }}" class="border-2 border-blue-600 text-blue-600 dark:text-blue-400 px-8 py-4 rounded-lg font-semibold hover:bg-blue-600 hover:text-white transition duration-300 flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                </svg>
                                {{ __('Create New Account') }}
                            </a>
                        </div>
                    </div>
                </section>
                @endguest
            </div>

            {{-- Footer --}}
            <footer class="bg-gray-800 text-white py-8 mt-16">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <p class="text-gray-300">
                        {{ __('Organization of') }} {{ __('8th Brazilian Conference on Statistical Modeling in Insurance and Finance') }}
                    </p>
                    <p class="text-gray-400 text-sm mt-2">
                        <a href="https://ime.usp.br" target="_blank" class="hover:text-gray-300 transition duration-300">
                            IME-USP
                        </a>
                    </p>
                </div>
            </footer>
        </div>
        @livewireScripts
    </body>
</html>