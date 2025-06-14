<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ __('Satellite Workshops') }} - {{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

        <!-- Scripts e Estilos via Vite -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased dark:bg-black dark:text-white/50">

        {{-- Cabeçalho USP --}}
        <x-usp.header />

        {{-- Navegação Pública --}}
        <x-layout.public-navigation />

        {{-- Container Principal --}}
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            
            {{-- Header Section --}}
            <div class="bg-gradient-to-r from-green-900 to-green-700 text-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                    <div class="text-center">
                        <h1 class="text-4xl md:text-6xl font-bold mb-6">
                            {{ __('Satellite Workshops') }}
                        </h1>
                        <p class="text-xl md:text-2xl mb-8 font-light">
                            {{ __('Two satellite workshops will be held before 8th BCSMIF') }}
                        </p>
                        <a href="/" class="bg-white text-green-900 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-300">
                            {{ __('Back to Home') }}
                        </a>
                    </div>
                </div>
            </div>

            {{-- Main Content --}}
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                
                {{-- Workshop 1: Risk Analysis and Applications --}}
                <section class="mb-16">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-red-600 to-red-500 text-white p-6">
                            <h2 class="text-3xl font-bold mb-2">
                                {{ __('Workshop on Risk Analysis and Applications (WRAA)') }}
                            </h2>
                            <p class="text-red-100">{{ __('September 24+25, 2025') }} | {{ __('At IME-USP, São Paulo') }}</p>
                        </div>
                        
                        <div class="p-8">
                            <div class="grid md:grid-cols-2 gap-8">
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                                        {{ __('Workshop Details') }}
                                    </h3>
                                    <ul class="space-y-3 text-gray-700 dark:text-gray-300">
                                        <li class="flex items-center">
                                            <svg class="w-5 h-5 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a4 4 0 118 0v4m-4 6v6m-4-6h8"></path>
                                            </svg>
                                            <strong>{{ __('Dates') }}:</strong> {{ __('September 24+25, 2025') }}
                                        </li>
                                        <li class="flex items-center">
                                            <svg class="w-5 h-5 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                            <strong>{{ __('Location') }}:</strong> {{ __('At IME-USP, São Paulo') }}
                                        </li>
                                        <li class="flex items-center">
                                            <svg class="w-5 h-5 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <strong>{{ __('Language') }}:</strong> {{ __('English') }}
                                        </li>
                                    </ul>
                                    
                                    <div class="mt-6">
                                        <h4 class="font-semibold text-gray-900 dark:text-white mb-3">{{ __('Focus Areas') }}:</h4>
                                        <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300 ml-4">
                                            <li>Risk modeling and quantification</li>
                                            <li>Applications in insurance and finance</li>
                                            <li>Advanced statistical methods</li>
                                            <li>Practical case studies</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                                        {{ __('Links and Resources') }}
                                    </h3>
                                    <div class="space-y-4">
                                        <a href="https://sites.google.com/usp.br/raa/" target="_blank" 
                                           class="flex items-center p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition duration-300">
                                            <div class="flex-1">
                                                <h4 class="font-semibold text-red-900 dark:text-red-400 mb-1">{{ __('Official Website') }}</h4>
                                                <p class="text-sm text-red-700 dark:text-red-300">{{ __('Get detailed information, program schedule, and speaker profiles') }}</p>
                                            </div>
                                            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                            </svg>
                                        </a>
                                        
                                        <div class="p-4 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg">
                                            <h4 class="font-semibold text-gray-900 dark:text-white mb-2">{{ __('Registration Information') }}</h4>
                                            <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                                                {{ __('Workshop registration fees vary by participant category. Check the fees page for complete information.') }}
                                            </p>
                                            <a href="/fees" class="inline-flex items-center text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 font-semibold">
                                                {{ __('View Registration Fees') }}
                                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Workshop 2: Dependence Analysis --}}
                <section class="mb-16">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-purple-600 to-purple-500 text-white p-6">
                            <h2 class="text-3xl font-bold mb-2">
                                {{ __('Workshop on Dependence Analysis (WDA)') }}
                            </h2>
                            <p class="text-purple-100">{{ __('September 26+27, 2025') }} | {{ __('At IMECC-UNICAMP, Campinas') }}</p>
                        </div>
                        
                        <div class="p-8">
                            <div class="grid md:grid-cols-2 gap-8">
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                                        {{ __('Workshop Details') }}
                                    </h3>
                                    <ul class="space-y-3 text-gray-700 dark:text-gray-300">
                                        <li class="flex items-center">
                                            <svg class="w-5 h-5 text-purple-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a4 4 0 118 0v4m-4 6v6m-4-6h8"></path>
                                            </svg>
                                            <strong>{{ __('Dates') }}:</strong> {{ __('September 26+27, 2025') }}
                                        </li>
                                        <li class="flex items-center">
                                            <svg class="w-5 h-5 text-purple-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                            <strong>{{ __('Location') }}:</strong> {{ __('At IMECC-UNICAMP, Campinas') }}
                                        </li>
                                        <li class="flex items-center">
                                            <svg class="w-5 h-5 text-purple-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <strong>{{ __('Language') }}:</strong> {{ __('English') }}
                                        </li>
                                    </ul>
                                    
                                    <div class="mt-6">
                                        <h4 class="font-semibold text-gray-900 dark:text-white mb-3">{{ __('Focus Areas') }}:</h4>
                                        <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300 ml-4">
                                            <li>Dependence structures and copulas</li>
                                            <li>Multivariate analysis</li>
                                            <li>Statistical dependence modeling</li>
                                            <li>Applications in risk management</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                                        {{ __('Links and Resources') }}
                                    </h3>
                                    <div class="space-y-4">
                                        <a href="https://sites.google.com/usp.br/wda-unicamp/" target="_blank" 
                                           class="flex items-center p-4 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/30 transition duration-300">
                                            <div class="flex-1">
                                                <h4 class="font-semibold text-purple-900 dark:text-purple-400 mb-1">{{ __('Official Website') }}</h4>
                                                <p class="text-sm text-purple-700 dark:text-purple-300">{{ __('Get detailed information, program schedule, and speaker profiles') }}</p>
                                            </div>
                                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                            </svg>
                                        </a>
                                        
                                        <div class="p-4 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg">
                                            <h4 class="font-semibold text-gray-900 dark:text-white mb-2">{{ __('Registration Information') }}</h4>
                                            <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                                                {{ __('Workshop registration fees vary by participant category. Check the fees page for complete information.') }}
                                            </p>
                                            <a href="/fees" class="inline-flex items-center text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-300 font-semibold">
                                                {{ __('View Registration Fees') }}
                                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Registration Call to Action --}}
                <section class="text-center">
                    <div class="bg-gradient-to-r from-blue-600 to-blue-500 text-white rounded-lg shadow-lg p-8">
                        <h2 class="text-2xl font-bold mb-4">
                            {{ __('Ready to Participate?') }}
                        </h2>
                        <p class="text-blue-100 mb-6">
                            {{ __('Register for one or both workshops along with the main 8th BCSMIF conference') }}
                        </p>
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            @guest
                                <a href="{{ route('register') }}" class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-300">
                                    {{ __('Register Now') }}
                                </a>
                            @endguest
                            @auth
                                <a href="{{ route('dashboard') }}" class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-300">
                                    {{ __('Go to Dashboard') }}
                                </a>
                            @endauth
                            <a href="/fees" class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-blue-600 transition duration-300">
                                {{ __('Check Registration Fees') }}
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            {{-- Footer --}}
            <footer class="bg-gray-800 text-white py-8 mt-16">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <p class="text-gray-300">
                        {{ __('Satellite Workshops') }} - {{ __('8th Brazilian Conference on Statistical Modeling in Insurance and Finance') }}
                    </p>
                    <p class="text-gray-400 text-sm mt-2">
                        Laravel v{{ Illuminate\Foundation\Application::VERSION }} (PHP v{{ PHP_VERSION }})
                    </p>
                </div>
            </footer>
        </div>
    </body>
</html>