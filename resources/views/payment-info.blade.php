<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ __('Payment Information') }} - {{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

        <!-- Scripts e Estilos via Vite -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased dark:bg-black dark:text-white/50">

        {{-- Cabeçalho USP --}}
        <x-usp.header />

        {{-- Container Principal --}}
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            
            {{-- Header Section --}}
            <div class="bg-gradient-to-r from-purple-900 to-purple-700 text-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                    <div class="text-center">
                        <h1 class="text-4xl md:text-6xl font-bold mb-6">
                            {{ __('Payment Information') }}
                        </h1>
                        <p class="text-xl md:text-2xl mb-8 font-light">
                            {{ __('Payment instructions and banking details') }}
                        </p>
                        <a href="/" class="bg-white text-purple-900 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-300">
                            {{ __('Back to Home') }}
                        </a>
                    </div>
                </div>
            </div>

            {{-- Main Content --}}
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                
                {{-- Brazilian Participants --}}
                <section class="mb-16">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-green-600 to-green-500 text-white p-6">
                            <h2 class="text-3xl font-bold mb-2">
                                {{ __('For Brazilian Participants') }}
                            </h2>
                            <p class="text-green-100">{{ __('Payment via bank transfer or PIX') }}</p>
                        </div>
                        
                        <div class="p-8">
                            <div class="grid md:grid-cols-2 gap-8">
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">
                                        {{ __('Bank Details') }}
                                    </h3>
                                    
                                    <div class="space-y-4">
                                        <div class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center mr-4">
                                                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-gray-900 dark:text-white">{{ __('Bank') }}</p>
                                                <p class="text-gray-700 dark:text-gray-300">{{ __('Banco Santander') }}</p>
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center mr-4">
                                                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-gray-900 dark:text-white">{{ __('Agency') }}</p>
                                                <p class="text-gray-700 dark:text-gray-300 font-mono">0658</p>
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center mr-4">
                                                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-gray-900 dark:text-white">{{ __('Account Number') }}</p>
                                                <p class="text-gray-700 dark:text-gray-300 font-mono">13006798-9</p>
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center mr-4">
                                                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-gray-900 dark:text-white">{{ __('Beneficiary Name') }}</p>
                                                <p class="text-gray-700 dark:text-gray-300">{{ __('Associação Brasileira de Estatística') }}</p>
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center mr-4">
                                                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-gray-900 dark:text-white">{{ __('CNPJ') }}</p>
                                                <p class="text-gray-700 dark:text-gray-300 font-mono">56.572.456/0001-80</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">
                                        {{ __('Payment Instructions') }}
                                    </h3>
                                    
                                    <div class="space-y-6">
                                        <div class="p-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                                            <h4 class="font-semibold text-green-900 dark:text-green-400 mb-3">
                                                {{ __('Step 1: Make Payment') }}
                                            </h4>
                                            <p class="text-green-800 dark:text-green-300 mb-4">
                                                {{ __('Transfer the registration amount to the bank account above using bank transfer or PIX.') }}
                                            </p>
                                            <div class="bg-green-100 dark:bg-green-900/30 p-3 rounded border border-green-300 dark:border-green-700">
                                                <p class="text-sm text-green-800 dark:text-green-300">
                                                    <strong>{{ __('Important') }}:</strong> {{ __('Make sure to include your full name and registration email in the payment reference.') }}
                                                </p>
                                            </div>
                                        </div>
                                        
                                        <div class="p-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                                            <h4 class="font-semibold text-blue-900 dark:text-blue-400 mb-3">
                                                {{ __('Step 2: Upload Proof') }}
                                            </h4>
                                            <p class="text-blue-800 dark:text-blue-300 mb-4">
                                                {{ __('After making payment, access your account and upload the payment proof through your dashboard.') }}
                                            </p>
                                            @auth
                                                <a href="{{ route('dashboard') }}" class="inline-flex items-center text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-semibold">
                                                    {{ __('Go to Dashboard') }}
                                                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                    </svg>
                                                </a>
                                            @else
                                                <a href="{{ route('register') }}" class="inline-flex items-center text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-semibold">
                                                    {{ __('Register First') }}
                                                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                    </svg>
                                                </a>
                                            @endauth
                                        </div>
                                        
                                        <div class="p-6 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg">
                                            <h4 class="font-semibold text-purple-900 dark:text-purple-400 mb-3">
                                                {{ __('Step 3: Confirmation') }}
                                            </h4>
                                            <p class="text-purple-800 dark:text-purple-300">
                                                {{ __('Your registration status will be updated to "Confirmed" once our team processes your payment proof.') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- International Participants --}}
                <section class="mb-16">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-600 to-blue-500 text-white p-6">
                            <h2 class="text-3xl font-bold mb-2">
                                {{ __('For International Participants') }}
                            </h2>
                            <p class="text-blue-100">{{ __('Invoice-based payment system') }}</p>
                        </div>
                        
                        <div class="p-8">
                            <div class="grid md:grid-cols-2 gap-8">
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">
                                        {{ __('How It Works') }}
                                    </h3>
                                    
                                    <div class="space-y-6">
                                        <div class="flex items-start">
                                            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mr-4 mt-1 flex-shrink-0">
                                                <span class="text-blue-600 dark:text-blue-400 font-bold text-sm">1</span>
                                            </div>
                                            <div>
                                                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">{{ __('Complete Registration') }}</h4>
                                                <p class="text-gray-700 dark:text-gray-300">{{ __('Register for the events through our registration system with your international details.') }}</p>
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-start">
                                            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mr-4 mt-1 flex-shrink-0">
                                                <span class="text-blue-600 dark:text-blue-400 font-bold text-sm">2</span>
                                            </div>
                                            <div>
                                                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">{{ __('Receive Invoice') }}</h4>
                                                <p class="text-gray-700 dark:text-gray-300">{{ __('Invoice will be sent to your registered email address after registration') }}</p>
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-start">
                                            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mr-4 mt-1 flex-shrink-0">
                                                <span class="text-blue-600 dark:text-blue-400 font-bold text-sm">3</span>
                                            </div>
                                            <div>
                                                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">{{ __('Process Payment') }}</h4>
                                                <p class="text-gray-700 dark:text-gray-300">{{ __('Follow the payment instructions in the invoice for international wire transfer or credit card payment.') }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">
                                        {{ __('Invoice Information') }}
                                    </h3>
                                    
                                    <div class="space-y-4">
                                        <div class="p-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                                            <div class="flex items-center mb-3">
                                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 7.89a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                                </svg>
                                                <h4 class="font-semibold text-blue-900 dark:text-blue-400">{{ __('Email Delivery') }}</h4>
                                            </div>
                                            <p class="text-blue-800 dark:text-blue-300">
                                                {{ __('International participants will receive an invoice with payment details') }}
                                            </p>
                                        </div>
                                        
                                        <div class="p-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                                            <div class="flex items-center mb-3">
                                                <svg class="w-6 h-6 text-green-600 dark:text-green-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                                </svg>
                                                <h4 class="font-semibold text-green-900 dark:text-green-400">{{ __('Payment Methods') }}</h4>
                                            </div>
                                            <ul class="text-green-800 dark:text-green-300 space-y-1">
                                                <li>• {{ __('International wire transfer') }}</li>
                                                <li>• {{ __('Credit card payment') }}</li>
                                                <li>• {{ __('PayPal (where available)') }}</li>
                                            </ul>
                                        </div>
                                        
                                        <div class="p-6 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                                            <div class="flex items-center mb-3">
                                                <svg class="w-6 h-6 text-amber-600 dark:text-amber-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <h4 class="font-semibold text-amber-900 dark:text-amber-400">{{ __('Processing Time') }}</h4>
                                            </div>
                                            <p class="text-amber-800 dark:text-amber-300">
                                                {{ __('Invoices are typically sent within 24-48 hours after registration completion.') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Call to Action --}}
                <section class="text-center">
                    <div class="bg-gradient-to-r from-purple-600 to-purple-500 text-white rounded-lg shadow-lg p-8">
                        <h2 class="text-2xl font-bold mb-4">
                            {{ __('Questions About Payment?') }}
                        </h2>
                        <p class="text-purple-100 mb-6">
                            {{ __('Check registration fees or contact our support team for assistance') }}
                        </p>
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <a href="/fees" class="bg-white text-purple-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-300">
                                {{ __('View Registration Fees') }}
                            </a>
                            @guest
                                <a href="{{ route('register') }}" class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-purple-600 transition duration-300">
                                    {{ __('Start Registration') }}
                                </a>
                            @endguest
                            @auth
                                <a href="{{ route('dashboard') }}" class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-purple-600 transition duration-300">
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
                        {{ __('Payment Information') }} - {{ __('8th Brazilian Conference on Statistical Modeling in Insurance and Finance') }}
                    </p>
                    <p class="text-gray-400 text-sm mt-2">
                        Laravel v{{ Illuminate\Foundation\Application::VERSION }} (PHP v{{ PHP_VERSION }})
                    </p>
                </div>
            </footer>
        </div>
    </body>
</html>