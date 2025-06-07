<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>8th BCSMIF - Brazilian Conference on Statistical Modeling in Insurance and Finance</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

        <!-- Scripts e Estilos via Vite -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased dark:bg-black dark:text-white/50">

        {{-- Cabeçalho USP --}}
        <x-usp.header />

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

        </div>
    </body>
</html>