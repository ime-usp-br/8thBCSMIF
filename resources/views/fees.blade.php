<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Registration Fees - 8th BCSMIF</title>

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
                        Registration Fees
                    </h1>
                    
                    <h2 class="text-xl lg:text-2xl font-semibold text-gray-800 dark:text-gray-200 mb-6">
                        8th BCSMIF Conference and Satellite Workshops
                    </h2>

                    <div class="prose prose-lg dark:prose-invert max-w-none">
                        <p class="text-gray-700 dark:text-gray-300 leading-relaxed mb-8">
                            Registration fees vary according to participant category, participation format, and registration period. Please review the complete fee structure below.
                        </p>

                        {{-- Main Conference Fees --}}
                        <div class="mb-8 p-6 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
                            <h3 class="text-2xl font-bold text-blue-900 dark:text-blue-200 mb-6 flex items-center">
                                🏛️ <span class="ml-2">8th BCSMIF Conference</span>
                            </h3>
                            
                            <div class="overflow-x-auto">
                                <table class="w-full border-collapse bg-white dark:bg-gray-800/70 rounded-lg shadow-lg">
                                    <thead>
                                        <tr class="bg-blue-100 dark:bg-blue-900/30">
                                            <th class="border border-blue-200 dark:border-blue-700 px-4 py-3 text-left font-semibold text-blue-900 dark:text-blue-200">Participant Category</th>
                                            <th class="border border-blue-200 dark:border-blue-700 px-4 py-3 text-center font-semibold text-blue-900 dark:text-blue-200">Until 08/15/2025<br>(In-person)</th>
                                            <th class="border border-blue-200 dark:border-blue-700 px-4 py-3 text-center font-semibold text-blue-900 dark:text-blue-200">After 08/15/2025<br>(In-person)</th>
                                            <th class="border border-blue-200 dark:border-blue-700 px-4 py-3 text-center font-semibold text-blue-900 dark:text-blue-200">Online</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="border-b border-blue-200 dark:border-blue-700">
                                            <td class="border border-blue-200 dark:border-blue-700 px-4 py-3 font-medium text-gray-900 dark:text-gray-200">Undergraduate Student</td>
                                            <td class="border border-blue-200 dark:border-blue-700 px-4 py-3 text-center text-green-600 dark:text-green-400 font-semibold">Free</td>
                                            <td class="border border-blue-200 dark:border-blue-700 px-4 py-3 text-center text-green-600 dark:text-green-400 font-semibold">Free</td>
                                            <td class="border border-blue-200 dark:border-blue-700 px-4 py-3 text-center text-green-600 dark:text-green-400 font-semibold">Free</td>
                                        </tr>
                                        <tr class="border-b border-blue-200 dark:border-blue-700 bg-blue-50/50 dark:bg-blue-900/10">
                                            <td class="border border-blue-200 dark:border-blue-700 px-4 py-3 font-medium text-gray-900 dark:text-gray-200">Graduate Student</td>
                                            <td class="border border-blue-200 dark:border-blue-700 px-4 py-3 text-center text-gray-700 dark:text-gray-300 font-semibold">R$ 600</td>
                                            <td class="border border-blue-200 dark:border-blue-700 px-4 py-3 text-center text-gray-700 dark:text-gray-300 font-semibold">R$ 700</td>
                                            <td class="border border-blue-200 dark:border-blue-700 px-4 py-3 text-center text-gray-700 dark:text-gray-300 font-semibold">R$ 200</td>
                                        </tr>
                                        <tr class="border-b border-blue-200 dark:border-blue-700">
                                            <td class="border border-blue-200 dark:border-blue-700 px-4 py-3 font-medium text-gray-900 dark:text-gray-200">Professor - ABE member</td>
                                            <td class="border border-blue-200 dark:border-blue-700 px-4 py-3 text-center text-gray-700 dark:text-gray-300 font-semibold">R$ 1,200</td>
                                            <td class="border border-blue-200 dark:border-blue-700 px-4 py-3 text-center text-gray-700 dark:text-gray-300 font-semibold">R$ 1,400</td>
                                            <td class="border border-blue-200 dark:border-blue-700 px-4 py-3 text-center text-gray-700 dark:text-gray-300 font-semibold">R$ 400</td>
                                        </tr>
                                        <tr class="border-b border-blue-200 dark:border-blue-700 bg-blue-50/50 dark:bg-blue-900/10">
                                            <td class="border border-blue-200 dark:border-blue-700 px-4 py-3 font-medium text-gray-900 dark:text-gray-200">Professor - ABE non-member / Professional</td>
                                            <td class="border border-blue-200 dark:border-blue-700 px-4 py-3 text-center text-gray-700 dark:text-gray-300 font-semibold">R$ 1,600</td>
                                            <td class="border border-blue-200 dark:border-blue-700 px-4 py-3 text-center text-gray-700 dark:text-gray-300 font-semibold">R$ 2,000</td>
                                            <td class="border border-blue-200 dark:border-blue-700 px-4 py-3 text-center text-gray-700 dark:text-gray-300 font-semibold">R$ 800</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Workshop Fees --}}
                        <div class="mb-8 p-6 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg border border-green-200 dark:border-green-700">
                            <h3 class="text-2xl font-bold text-green-900 dark:text-green-200 mb-6 flex items-center">
                                🎯 <span class="ml-2">Satellite Workshops (each one)</span>
                            </h3>
                            
                            <div class="overflow-x-auto mb-4">
                                <table class="w-full border-collapse bg-white dark:bg-gray-800/70 rounded-lg shadow-lg">
                                    <thead>
                                        <tr class="bg-green-100 dark:bg-green-900/30">
                                            <th class="border border-green-200 dark:border-green-700 px-4 py-3 text-left font-semibold text-green-900 dark:text-green-200">Participant Category</th>
                                            <th class="border border-green-200 dark:border-green-700 px-4 py-3 text-center font-semibold text-green-900 dark:text-green-200">Until 08/15/2025<br>(In-person)</th>
                                            <th class="border border-green-200 dark:border-green-700 px-4 py-3 text-center font-semibold text-green-900 dark:text-green-200">After 08/15/2025<br>(In-person)</th>
                                            <th class="border border-green-200 dark:border-green-700 px-4 py-3 text-center font-semibold text-green-900 dark:text-green-200">Online</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="border-b border-green-200 dark:border-green-700">
                                            <td class="border border-green-200 dark:border-green-700 px-4 py-3 font-medium text-gray-900 dark:text-gray-200">Undergraduate Student</td>
                                            <td class="border border-green-200 dark:border-green-700 px-4 py-3 text-center text-green-600 dark:text-green-400 font-semibold">Free</td>
                                            <td class="border border-green-200 dark:border-green-700 px-4 py-3 text-center text-green-600 dark:text-green-400 font-semibold">Free</td>
                                            <td class="border border-green-200 dark:border-green-700 px-4 py-3 text-center text-green-600 dark:text-green-400 font-semibold">Free</td>
                                        </tr>
                                        <tr class="border-b border-green-200 dark:border-green-700 bg-green-50/50 dark:bg-green-900/10">
                                            <td class="border border-green-200 dark:border-green-700 px-4 py-3 font-medium text-gray-900 dark:text-gray-200">Graduate Student</td>
                                            <td class="border border-green-200 dark:border-green-700 px-4 py-3 text-center text-green-600 dark:text-green-400 font-semibold">Free</td>
                                            <td class="border border-green-200 dark:border-green-700 px-4 py-3 text-center text-green-600 dark:text-green-400 font-semibold">Free</td>
                                            <td class="border border-green-200 dark:border-green-700 px-4 py-3 text-center text-green-600 dark:text-green-400 font-semibold">Free</td>
                                        </tr>
                                        <tr class="border-b border-green-200 dark:border-green-700">
                                            <td class="border border-green-200 dark:border-green-700 px-4 py-3 font-medium text-gray-900 dark:text-gray-200">Professor - ABE member</td>
                                            <td class="border border-green-200 dark:border-green-700 px-4 py-3 text-center text-gray-700 dark:text-gray-300 font-semibold">R$ 250 <span class="text-sm text-gray-500 dark:text-gray-400">(R$ 100*)</span></td>
                                            <td class="border border-green-200 dark:border-green-700 px-4 py-3 text-center text-gray-700 dark:text-gray-300 font-semibold">R$ 350 <span class="text-sm text-gray-500 dark:text-gray-400">(R$ 200*)</span></td>
                                            <td class="border border-green-200 dark:border-green-700 px-4 py-3 text-center text-gray-700 dark:text-gray-300 font-semibold">R$ 150 <span class="text-sm text-gray-500 dark:text-gray-400">(R$ 100*)</span></td>
                                        </tr>
                                        <tr class="border-b border-green-200 dark:border-green-700 bg-green-50/50 dark:bg-green-900/10">
                                            <td class="border border-green-200 dark:border-green-700 px-4 py-3 font-medium text-gray-900 dark:text-gray-200">Professor - ABE non-member / Professional</td>
                                            <td class="border border-green-200 dark:border-green-700 px-4 py-3 text-center text-gray-700 dark:text-gray-300 font-semibold">R$ 700 <span class="text-sm text-gray-500 dark:text-gray-400">(R$ 500*)</span></td>
                                            <td class="border border-green-200 dark:border-green-700 px-4 py-3 text-center text-gray-700 dark:text-gray-300 font-semibold">R$ 850 <span class="text-sm text-gray-500 dark:text-gray-400">(R$ 650*)</span></td>
                                            <td class="border border-green-200 dark:border-green-700 px-4 py-3 text-center text-gray-700 dark:text-gray-300 font-semibold">R$ 350 <span class="text-sm text-gray-500 dark:text-gray-400">(R$ 200*)</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg p-4">
                                <p class="text-sm text-amber-800 dark:text-amber-200 flex items-start">
                                    <span class="text-lg mr-2">⚠️</span>
                                    <span><strong>* Discounted prices in parentheses apply to 8th BCSMIF main conference participants.</strong></span>
                                </p>
                            </div>
                        </div>

                        {{-- Important Information --}}
                        <div class="bg-gray-50 dark:bg-gray-800/50 p-6 rounded-lg border border-gray-200 dark:border-gray-700">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-200 mb-4 flex items-center">
                                📋 <span class="ml-2">Important Information</span>
                            </h3>
                            <ul class="space-y-2 text-gray-700 dark:text-gray-300">
                                <li class="flex items-start">
                                    <span class="text-blue-500 mr-2">•</span>
                                    <span>Undergraduate students attend all events free of charge</span>
                                </li>
                                <li class="flex items-start">
                                    <span class="text-blue-500 mr-2">•</span>
                                    <span>Graduate students attend satellite workshops free of charge</span>
                                </li>
                                <li class="flex items-start">
                                    <span class="text-blue-500 mr-2">•</span>
                                    <span>Registration deadline for early bird prices: August 15, 2025</span>
                                </li>
                                <li class="flex items-start">
                                    <span class="text-blue-500 mr-2">•</span>
                                    <span>ABE (Brazilian Statistical Association) membership provides reduced fees</span>
                                </li>
                                <li class="flex items-start">
                                    <span class="text-blue-500 mr-2">•</span>
                                    <span>Online participation is available at reduced rates for all events</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    {{-- Navigation Links --}}
                    <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex flex-wrap gap-4">
                            <a href="{{ url('/') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-md transition duration-150 ease-in-out">
                                🏠 Back to Home
                            </a>
                            <a href="{{ route('workshops') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition duration-150 ease-in-out">
                                🎯 View Workshops
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

        </div>
    </body>
</html>