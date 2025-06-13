<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ __('Registration Fees') }} - {{ config('app.name', 'Laravel') }}</title>

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
                            {{ __('Registration Fees') }}
                        </h1>
                        <p class="text-xl md:text-2xl mb-8 font-light">
                            {{ __('Complete fee information for all events and participant categories') }}
                        </p>
                        <a href="/" class="bg-white text-green-900 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-300">
                            {{ __('Back to Home') }}
                        </a>
                    </div>
                </div>
            </div>

            {{-- Main Content --}}
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                
                {{-- 8th BCSMIF Conference Fees --}}
                <section class="mb-16">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-600 to-blue-500 text-white p-6">
                            <h2 class="text-3xl font-bold mb-2">
                                {{ __('8th BCSMIF Conference') }}
                            </h2>
                            <p class="text-blue-100">{{ __('September 28 to October 3, 2025') }} | {{ __('Maresias Beach Hotel, Maresias, SP') }}</p>
                        </div>
                        
                        <div class="p-6 overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 font-semibold">{{ __('Participant Category') }}</th>
                                        <th scope="col" class="px-6 py-3 text-center font-semibold">{{ __('Until 08/15/2025') }}<br><span class="text-xs font-normal normal-case">{{ __('In-person') }}</span></th>
                                        <th scope="col" class="px-6 py-3 text-center font-semibold">{{ __('After 08/15/2025') }}<br><span class="text-xs font-normal normal-case">{{ __('In-person') }}</span></th>
                                        <th scope="col" class="px-6 py-3 text-center font-semibold">{{ __('Online') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                            {{ __('Undergraduate Student') }}
                                        </th>
                                        <td class="px-6 py-4 text-center font-semibold text-green-600 dark:text-green-400">{{ __('Free') }}</td>
                                        <td class="px-6 py-4 text-center font-semibold text-green-600 dark:text-green-400">{{ __('Free') }}</td>
                                        <td class="px-6 py-4 text-center font-semibold text-green-600 dark:text-green-400">{{ __('Free') }}</td>
                                    </tr>
                                    <tr class="bg-gray-50 border-b dark:bg-gray-700 dark:border-gray-600">
                                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                            {{ __('Graduate Student') }}
                                        </th>
                                        <td class="px-6 py-4 text-center font-semibold">R$ 600</td>
                                        <td class="px-6 py-4 text-center font-semibold">R$ 700</td>
                                        <td class="px-6 py-4 text-center font-semibold">R$ 200</td>
                                    </tr>
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                            {{ __('Professor - ABE member') }}
                                        </th>
                                        <td class="px-6 py-4 text-center font-semibold">R$ 1,200</td>
                                        <td class="px-6 py-4 text-center font-semibold">R$ 1,400</td>
                                        <td class="px-6 py-4 text-center font-semibold">R$ 400</td>
                                    </tr>
                                    <tr class="bg-gray-50 border-b dark:bg-gray-700 dark:border-gray-600">
                                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                            {{ __('Professor - ABE non-member / Professional') }}
                                        </th>
                                        <td class="px-6 py-4 text-center font-semibold">R$ 1,600</td>
                                        <td class="px-6 py-4 text-center font-semibold">R$ 2,000</td>
                                        <td class="px-6 py-4 text-center font-semibold">R$ 800</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                {{-- Workshop Fees --}}
                <section class="mb-16">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-purple-600 to-purple-500 text-white p-6">
                            <h2 class="text-3xl font-bold mb-2">
                                {{ __('Satellite Workshops') }}
                            </h2>
                            <p class="text-purple-100">{{ __('Workshop (each one)') }} - {{ __('September 24+25 and 26+27, 2025') }}</p>
                        </div>
                        
                        <div class="p-6 overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 font-semibold">{{ __('Participant Category') }}</th>
                                        <th scope="col" class="px-6 py-3 text-center font-semibold">{{ __('Until 08/15/2025') }}<br><span class="text-xs font-normal normal-case">{{ __('In-person') }}</span></th>
                                        <th scope="col" class="px-6 py-3 text-center font-semibold">{{ __('After 08/15/2025') }}<br><span class="text-xs font-normal normal-case">{{ __('In-person') }}</span></th>
                                        <th scope="col" class="px-6 py-3 text-center font-semibold">{{ __('Online') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                            {{ __('Undergraduate Student') }}
                                        </th>
                                        <td class="px-6 py-4 text-center font-semibold text-green-600 dark:text-green-400">{{ __('Free') }}</td>
                                        <td class="px-6 py-4 text-center font-semibold text-green-600 dark:text-green-400">{{ __('Free') }}</td>
                                        <td class="px-6 py-4 text-center font-semibold text-green-600 dark:text-green-400">{{ __('Free') }}</td>
                                    </tr>
                                    <tr class="bg-gray-50 border-b dark:bg-gray-700 dark:border-gray-600">
                                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                            {{ __('Graduate Student') }}
                                        </th>
                                        <td class="px-6 py-4 text-center font-semibold text-green-600 dark:text-green-400">{{ __('Free') }}</td>
                                        <td class="px-6 py-4 text-center font-semibold text-green-600 dark:text-green-400">{{ __('Free') }}</td>
                                        <td class="px-6 py-4 text-center font-semibold text-green-600 dark:text-green-400">{{ __('Free') }}</td>
                                    </tr>
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                            {{ __('Professor - ABE member') }}
                                        </th>
                                        <td class="px-6 py-4 text-center">
                                            <span class="font-semibold">R$ 250</span>
                                            <span class="text-sm text-purple-600 dark:text-purple-400 block">(R$ 100)*</span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="font-semibold">R$ 350</span>
                                            <span class="text-sm text-purple-600 dark:text-purple-400 block">(R$ 200)*</span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="font-semibold">R$ 150</span>
                                            <span class="text-sm text-purple-600 dark:text-purple-400 block">(R$ 100)*</span>
                                        </td>
                                    </tr>
                                    <tr class="bg-gray-50 border-b dark:bg-gray-700 dark:border-gray-600">
                                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                            {{ __('Professor - ABE non-member / Professional') }}
                                        </th>
                                        <td class="px-6 py-4 text-center">
                                            <span class="font-semibold">R$ 700</span>
                                            <span class="text-sm text-purple-600 dark:text-purple-400 block">(R$ 500)*</span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="font-semibold">R$ 850</span>
                                            <span class="text-sm text-purple-600 dark:text-purple-400 block">(R$ 650)*</span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="font-semibold">R$ 350</span>
                                            <span class="text-sm text-purple-600 dark:text-purple-400 block">(R$ 200)*</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                {{-- Important Information --}}
                <section class="mb-16">
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-8">
                        <div class="flex items-start">
                            <svg class="w-6 h-6 text-amber-600 dark:text-amber-400 mr-3 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>
                                <h3 class="text-xl font-semibold text-amber-900 dark:text-amber-400 mb-4">
                                    {{ __('Important Information') }}
                                </h3>
                                <ul class="space-y-3 text-amber-800 dark:text-amber-300">
                                    <li class="flex items-start">
                                        <span class="font-semibold mr-2">*</span>
                                        {{ __('Numbers in parentheses refer to discounts for 8th BCSMIF participants') }}
                                    </li>
                                    <li class="flex items-start">
                                        <span class="font-semibold mr-2">•</span>
                                        {{ __('Undergraduate students are exempt from fees for all events') }}
                                    </li>
                                    <li class="flex items-start">
                                        <span class="font-semibold mr-2">•</span>
                                        {{ __('Graduate students are exempt from workshop fees') }}
                                    </li>
                                    <li class="flex items-start">
                                        <span class="font-semibold mr-2">•</span>
                                        {{ __('Early bird registration deadline: August 15, 2025') }}
                                    </li>
                                    <li class="flex items-start">
                                        <span class="font-semibold mr-2">•</span>
                                        {{ __('ABE membership status will be verified during registration') }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Payment Information Call to Action --}}
                <section class="text-center">
                    <div class="bg-gradient-to-r from-green-600 to-green-500 text-white rounded-lg shadow-lg p-8">
                        <h2 class="text-2xl font-bold mb-4">
                            {{ __('Ready to Register?') }}
                        </h2>
                        <p class="text-green-100 mb-6">
                            {{ __('Get payment instructions and banking details for Brazilian and international participants') }}
                        </p>
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <a href="/payment-info" class="bg-white text-green-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-300">
                                {{ __('Payment Instructions') }}
                            </a>
                            @guest
                                <a href="{{ route('register') }}" class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-green-600 transition duration-300">
                                    {{ __('Register Now') }}
                                </a>
                            @endguest
                            @auth
                                <a href="{{ route('dashboard') }}" class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-green-600 transition duration-300">
                                    {{ __('Go to Dashboard') }}
                                </a>
                            @endauth
                        </div>
                    </div>
                </section>
            </div>

            {{-- Footer --}}
            <footer class="bg-gray-800 text-white py-8 mt-16">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <p class="text-gray-300">
                        {{ __('Registration Fees') }} - {{ __('8th Brazilian Conference on Statistical Modeling in Insurance and Finance') }}
                    </p>
                    <p class="text-gray-400 text-sm mt-2">
                        Laravel v{{ Illuminate\Foundation\Application::VERSION }} (PHP v{{ PHP_VERSION }})
                    </p>
                </div>
            </footer>
        </div>
    </body>
</html>