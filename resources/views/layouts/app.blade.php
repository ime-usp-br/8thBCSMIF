<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            
            {{-- Inclui o cabeçalho USP --}}
            <x-usp.header />

            <x-layout.public-navigation />

            <!-- Page Heading -->
            <!-- @if (isset($header))
                <header class="bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif -->

            <!-- Page Content -->
            <main>
                {{-- Global Flash Messages --}}
                @if(session('error') || session('success'))
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                        @if(session('error'))
                            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                                <strong class="font-bold">{{ __('Error!') }}</strong>
                                <span class="block sm:inline">{{ session('error') }}</span>
                            </div>
                        @endif
                        
                        @if(session('success'))
                            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                                <strong class="font-bold">{{ __('Success!') }}</strong>
                                <span class="block sm:inline">{{ session('success') }}</span>
                            </div>
                        @endif
                    </div>
                @endif
                
                {{ $slot }}
            </main>
        </div>
        @livewireScripts
    </body>
</html>
