<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

<<<<<<< Updated upstream
        <title>Satellite Workshops - 8th BCSMIF</title>
=======
        <title>{{ __('Satellite Workshops') }} - {{ config('app.name', 'Laravel') }}</title>
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
                        Satellite Workshops
                    </h1>
                    
                    <h2 class="text-xl lg:text-2xl font-semibold text-gray-800 dark:text-gray-200 mb-6">
                        8th BCSMIF Pre-Conference Workshops
                    </h2>

                    <div class="prose prose-lg dark:prose-invert max-w-none">
                        <p class="text-gray-700 dark:text-gray-300 leading-relaxed mb-8">
                            Two satellite workshops will be held before the 8th BCSMIF conference, providing specialized content and preparation for the main event.
                        </p>

                        {{-- Workshop 1: Risk Analysis and Applications (WRAA) --}}
                        <div class="mb-8 p-6 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
                            <div class="flex items-center mb-4">
                                <img src="https://lh6.googleusercontent.com/H9g3mfD_L1UkpDkliNw0Yyw_pA5poTWRokcXjK4PmobzffI4cUL83Eopa2-lf4SXwrJjATnsHd-kRBInkWQPs5i07eyEBZBPk-mEk2EUJcPuoXnyAS-vRx6PW0dhp3F_fuW_HrSsrkXhcjS2F6orMvrE8yagamx9JOHTUOGw7LUGIgOgevgpnQ=w1280" alt="WRAA Logo" class="w-24 h-auto mr-4 rounded">
                                <div>
                                    <h3 class="text-2xl font-bold text-blue-900 dark:text-blue-200 mb-2">
                                        <a href="https://sites.google.com/usp.br/raa/" class="hover:underline" target="_blank" rel="noopener">
                                            Workshop on Risk Analysis and Applications (WRAA)
                                        </a>
                                    </h3>
                                    <a href="https://drive.google.com/file/d/1BjmtBq16OnWQJT6G0lAckuwnukluEWW8/view?usp=drive_link" class="text-blue-600 dark:text-blue-400 hover:underline text-sm" target="_blank" rel="noopener">
                                        📄 Download Flyer
                                    </a>
                                </div>
                            </div>
                            
                            <div class="grid md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <h4 class="font-semibold text-blue-800 dark:text-blue-200 mb-2 flex items-center">
                                        📅 <span class="ml-2">Dates</span>
                                    </h4>
                                    <p class="text-gray-700 dark:text-gray-300">September 24-25, 2025</p>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-blue-800 dark:text-blue-200 mb-2 flex items-center">
                                        📍 <span class="ml-2">Location</span>
                                    </h4>
                                    <p class="text-gray-700 dark:text-gray-300">Institute of Mathematics and Statistics of University of São Paulo</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Rua do Matão, 1010 CEP: 05508-090, São Paulo - SP, Brazil</p>
                                </div>
                            </div>
                            
                            <div class="bg-white/70 dark:bg-gray-800/70 p-4 rounded-lg mb-4">
                                <p class="text-gray-700 dark:text-gray-300 leading-relaxed mb-4">
                                    Risk Analysis and Applications is a satellite workshop to the 8th Brazilian Conference on Stochastic Modeling in Insurance and Finance. It will be held for two days of the week that precedes the Conference. It will be focused on the Risk Analysis that is one of the Conference topics.
                                </p>
                                
                                <h5 class="font-semibold text-blue-800 dark:text-blue-200 mb-2">Program Features:</h5>
                                <ul class="list-disc pl-5 text-gray-700 dark:text-gray-300 space-y-1">
                                    <li>Approximately 8 plenary lectures on Risk Analysis and closely related topics</li>
                                    <li>Software presentations</li>
                                    <li>Round table discussions</li>
                                    <li>Poster session</li>
                                    <li>Mini course at the level appropriate for undergraduate students</li>
                                </ul>
                                
                                <p class="text-gray-700 dark:text-gray-300 leading-relaxed mt-4">
                                    Researchers, practitioners, undergraduate and graduate students are encouraged to participate and are welcome to contribute with their expertise to the software presentations and the round table discussions as well as to present their current research work and/or problems in the form of posters.
                                </p>
                            </div>
                        </div>

                        {{-- Workshop 2: Dependence Analysis (WDA) --}}
                        <div class="mb-8 p-6 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg border border-green-200 dark:border-green-700">
                            <div class="flex items-center mb-4">
                                <img src="https://lh4.googleusercontent.com/lSi0Q-8M5OI52mtezhfGyq-MEpbMpltvOu1IsST4LRvFVqJiv6SeKuhmgkpndnL4FkYVdow-fiPs4RI7ToDWbXvAnDkCyG4ejiy1D1rPKR8JSy03rdkUj-VEamT1RTwsyGUPBxA1WGDAz6xvEd7jy7gOtYGJe2pNiQlryshpAda4bXMFZ2bt2Q=w1280" alt="WDA Logo" class="w-24 h-auto mr-4 rounded">
                                <div>
                                    <h3 class="text-2xl font-bold text-green-900 dark:text-green-200 mb-2">
                                        <a href="https://sites.google.com/usp.br/wda-unicamp/" class="hover:underline" target="_blank" rel="noopener">
                                            Workshop on Dependence Analysis (WDA)
                                        </a>
                                    </h3>
                                    <a href="https://drive.google.com/file/d/1Ewk0Af3zo6m_VQoFxTfe5Fbb3KpDsQgE/view?usp=drive_link" class="text-green-600 dark:text-green-400 hover:underline text-sm" target="_blank" rel="noopener">
                                        📄 Download Flyer
                                    </a>
                                </div>
                            </div>
                            
                            <div class="grid md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <h4 class="font-semibold text-green-800 dark:text-green-200 mb-2 flex items-center">
                                        📅 <span class="ml-2">Dates</span>
                                    </h4>
                                    <p class="text-gray-700 dark:text-gray-300">September 26-27, 2025</p>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-green-800 dark:text-green-200 mb-2 flex items-center">
                                        📍 <span class="ml-2">Location</span>
                                    </h4>
                                    <p class="text-gray-700 dark:text-gray-300">ADunicamp (Sep 26) and IMECC-UNICAMP (Sep 27)</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Institute of Mathematics, Statistics and Scientific Computing - State University of Campinas</p>
                                </div>
                            </div>
                            
                            <div class="bg-white/70 dark:bg-gray-800/70 p-4 rounded-lg mb-4">
                                <p class="text-gray-700 dark:text-gray-300 leading-relaxed mb-4">
                                    The Workshop aims to provide a platform for presenting cutting-edge research on the development and implementation of multivariate dependency models and their applications in finance and actuarial science. It will also serve as a forum for discussing both national and international issues of professional interest.
                                </p>
                                
                                <p class="text-gray-700 dark:text-gray-300 leading-relaxed mb-4">
                                    The event is specifically designed to promote collaboration between researchers and industry professionals in these fields, while fostering a dynamic exchange of ideas between emerging researchers and experts with established experience and global recognition.
                                </p>
                                
                                <h5 class="font-semibold text-green-800 dark:text-green-200 mb-2">Program Features:</h5>
                                <ul class="list-disc pl-5 text-gray-700 dark:text-gray-300 space-y-1">
                                    <li>Approximately 10 plenary lectures on multivariate statistical models and their applications</li>
                                    <li>Mini-course presented at a level suitable for graduate students</li>
                                    <li>Software sessions</li>
                                    <li>Poster presentations (estimated 35 posters showcasing recent research)</li>
                                </ul>
                                
                                <p class="text-gray-700 dark:text-gray-300 leading-relaxed mt-4">
                                    Brazilian researchers are strongly encouraged to participate, as they will have the opportunity to receive valuable feedback, suggestions, and critiques from high-level international experts.
                                </p>
                            </div>
                        </div>

                        {{-- Timeline Overview --}}
                        <div class="bg-blue-50 dark:bg-blue-900/20 p-6 rounded-lg border border-blue-200 dark:border-blue-700">
                            <h3 class="text-xl font-bold text-blue-900 dark:text-blue-200 mb-4">📊 Conference Timeline</h3>
                            <div class="space-y-3">
                                <div class="flex items-center space-x-3">
                                    <span class="w-3 h-3 bg-blue-500 rounded-full"></span>
                                    <span class="text-gray-700 dark:text-gray-300"><strong>Sep 24-25:</strong> Risk Analysis and Applications (IME-USP)</span>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <span class="w-3 h-3 bg-blue-500 rounded-full"></span>
                                    <span class="text-gray-700 dark:text-gray-300"><strong>Sep 26-27:</strong> Dependence Analysis (IMECC-UNICAMP)</span>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <span class="w-3 h-3 bg-green-500 rounded-full"></span>
                                    <span class="text-gray-700 dark:text-gray-300"><strong>Sep 28 - Oct 3:</strong> 8th BCSMIF Main Conference (Maresias Beach Hotel)</span>
=======
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
>>>>>>> Stashed changes
                                </div>
                            </div>
                        </div>
                    </div>
<<<<<<< Updated upstream

                    {{-- Navigation Links --}}
                    <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex flex-wrap gap-4">
                            <a href="{{ url('/') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-md transition duration-150 ease-in-out">
                                🏠 Back to Home
                            </a>
                            @guest
                                <a href="{{ route('login.local') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 font-medium rounded-md transition duration-150 ease-in-out">
                                    🔐 Login
                                </a>
                            @endguest
                            @auth
                                <a href="{{ route('register') }}" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-md transition duration-150 ease-in-out">
                                    ✅ Register for the Conference
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
>>>>>>> Stashed changes
        </div>
    </body>
</html>