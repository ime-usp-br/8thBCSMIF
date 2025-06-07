<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Satellite Workshops - 8th BCSMIF</title>

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
                                </div>
                            </div>
                        </div>
                    </div>

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

        </div>
    </body>
</html>