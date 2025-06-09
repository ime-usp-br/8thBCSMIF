<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

<<<<<<< Updated upstream
        <title>8th BCSMIF - Brazilian Conference on Statistical Modeling in Insurance and Finance</title>
=======
        <title>{{ __('Welcome to 8th BCSMIF') }} - {{ config('app.name', 'Laravel') }}</title>
>>>>>>> Stashed changes

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

        <!-- Scripts e Estilos via Vite -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased dark:bg-black dark:text-white/50">

        {{-- Cabeçalho USP --}}
        <x-usp.header />

<<<<<<< Updated upstream
        {{-- Container Geral Flexível Verticalmente --}}
        <div class="relative min-h-screen flex flex-col bg-gray-100 dark:bg-gray-900">

            {{-- Container do Conteúdo Principal (Controla largura e cresce verticalmente) --}}
            <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col flex-grow py-8 md:py-6">

                {{-- Área principal (O card em si) --}}
                <div class="p-6 lg:p-8 bg-white dark:bg-gray-800/50 dark:bg-gradient-to-bl from-gray-700/50 via-transparent dark:ring-1 dark:ring-inset dark:ring-white/5 rounded-lg shadow-2xl shadow-gray-500/20 dark:shadow-none flex flex-col flex-grow">
                    <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 dark:text-white mb-6">
                        8th BCSMIF
                    </h1>
                    
                    <h2 class="text-xl lg:text-2xl font-semibold text-gray-800 dark:text-gray-200 mb-4">
                        Brazilian Conference on Statistical Modeling in Insurance and Finance
                    </h2>

                    <div class="prose prose-lg dark:prose-invert max-w-none">
                        <p class="text-gray-700 dark:text-gray-300 leading-relaxed mb-4">
                            The Institute of Mathematics and Statistics of the University of São Paulo (IME-USP) is announcing the Eighth Brazilian Conference on Statistical Modeling in Insurance and Finance (8th BCSMIF) to be held from <strong>September 28 to October 3, 2025</strong> at the Maresias Beach Hotel in Maresias, SP.
                        </p>

                        <p class="text-gray-700 dark:text-gray-300 leading-relaxed mb-4">
                            The 8th BCSMIF objective is to provide a forum for presenting cutting-edge research on the development, implementation of recent methods in the field of Finance and Insurance, with emphasize on the practical applications of Data Science and Machine Learning.
                        </p>

                        <p class="text-gray-700 dark:text-gray-300 leading-relaxed mb-4">
                            The 8th BCSMIF also seeks to promote discussion and the exchange of ideas between young researchers and senior scientists. Traditionally, the event involves graduate students to facilitate their integration into the academic and scientific environment. All speakers are invited to include relevant examples in their presentations.
                        </p>

                        <p class="text-gray-700 dark:text-gray-300 leading-relaxed mb-6">
                            The 8th BCSMIF is open to academic and non-academic communities, including universities, insurance companies, banks, consulting firms, and government agencies. The conference aims to foster cooperation between professionals and researchers in the field. The official language is English.
                        </p>

                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3">
                            Satellite Workshops
                        </h3>

                        <p class="text-gray-700 dark:text-gray-300 leading-relaxed mb-4">
                            Two satellite workshops will be held before 8th BCSMIF:
                        </p>

                        <ul class="space-y-2 text-gray-700 dark:text-gray-300">
                            <li>
                                <a href="https://sites.google.com/usp.br/raa/" class="text-blue-600 dark:text-blue-400 hover:underline font-medium" target="_blank" rel="noopener">
                                    Risk Analysis and Applications
                                </a>
                                (September 24+25, 2025) at the Institute of Mathematics and Statistics of the University of São Paulo (IME-USP)
                            </li>
                            <li>
                                <a href="https://sites.google.com/usp.br/wda-unicamp/" class="text-blue-600 dark:text-blue-400 hover:underline font-medium" target="_blank" rel="noopener">
                                    Dependence Analysis
                                </a>
                                (September 26+27, 2025) at the Institute of Mathematics, Statistics and Scientific Computing of the State University of Campinas (IMECC-UNICAMP)
                            </li>
                        </ul>
                    </div>

                    {{-- Navigation Links --}}
                    <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex flex-wrap gap-4">
                            @guest
                                <a href="{{ route('workshops') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-md transition duration-150 ease-in-out">
                                    📋 Workshops
                                </a>
                                <a href="{{ route('login.local') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 font-medium rounded-md transition duration-150 ease-in-out">
                                    🔐 Login
                                </a>
                            @endguest
                            @auth
                                <a href="{{ route('register') }}" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-md transition duration-150 ease-in-out">
                                    ✅ Register for the Conference
                                </a>
                                <a href="{{ route('workshops') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-md transition duration-150 ease-in-out">
                                    📋 Workshops
                                </a>
                                <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition duration-150 ease-in-out">
                                    🏠 Access Dashboard
                                </a>
                            @endauth
                        </div>
                    </div>
                </div>

            </div>

            {{-- Rodapé padrão --}}
            <footer class="py-8 text-center text-sm text-black dark:text-white/70 bg-gray-100 dark:bg-gray-900">
                8th BCSMIF - Brazilian Conference on Statistical Modeling in Insurance and Finance
            </footer>

=======
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
                                <a href="{{ route('register') }}" class="bg-white text-blue-900 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-300">
                                    {{ __('Register Now') }}
                                </a>
                            @endguest
                            @auth
                                <a href="{{ route('dashboard') }}" class="bg-white text-blue-900 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-300">
                                    {{ __('Dashboard') }}
                                </a>
                            @endauth
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

                {{-- Auth Links for Guests --}}
                @guest
                <section class="text-center">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                            {{ __('Ready to Join?') }}
                        </h2>
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <a href="{{ route('login') }}" class="bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700 transition duration-300">
                                {{ __('Login with Senha Única USP') }}
                            </a>
                            <a href="{{ route('register') }}" class="border-2 border-blue-600 text-blue-600 dark:text-blue-400 px-8 py-3 rounded-lg font-semibold hover:bg-blue-600 hover:text-white transition duration-300">
                                {{ __('Register') }}
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
                        Laravel v{{ Illuminate\Foundation\Application::VERSION }} (PHP v{{ PHP_VERSION }})
                    </p>
                </div>
            </footer>
>>>>>>> Stashed changes
        </div>
    </body>
</html>