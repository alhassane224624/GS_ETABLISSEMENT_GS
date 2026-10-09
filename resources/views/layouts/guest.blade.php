<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ \App\Support\Etablissement::get('nom') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <!-- Fond dégradé animé -->
        <div class="min-h-screen relative overflow-hidden bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50">
            <!-- Motifs décoratifs d'arrière-plan -->
            <div class="absolute inset-0 overflow-hidden">
                <div class="absolute -top-40 -right-40 w-80 h-80 bg-purple-300 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob"></div>
                <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-indigo-300 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob animation-delay-2000"></div>
                <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-80 h-80 bg-pink-300 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob animation-delay-4000"></div>
            </div>

            <!-- Contenu principal -->
            <div class="relative min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 px-4">
                <!-- Logo -->
                <div class="mb-8 transform transition-all duration-300 hover:scale-105">
                    <a href="/" class="block">
                        <x-application-logo class="w-24 h-24 drop-shadow-lg" />
                    </a>
                </div>

                <!-- Carte principale -->
                <div class="w-full sm:max-w-md">
                    <div class="bg-white/80 backdrop-blur-lg shadow-2xl rounded-2xl overflow-hidden border border-white/20">
                        <div class="px-8 py-10">
                            {{ $slot }}
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-8 text-center text-sm text-gray-600">
                    <p>&copy; {{ date('Y') }} {{ \App\Support\Etablissement::get('nom') }}. Tous droits réservés.</p>
                </div>
            </div>
        </div>

        <style>
            @keyframes blob {
                0%, 100% { transform: translate(0, 0) scale(1); }
                25% { transform: translate(20px, -20px) scale(1.1); }
                50% { transform: translate(-20px, 20px) scale(0.9); }
                75% { transform: translate(20px, 20px) scale(1.05); }
            }
            .animate-blob {
                animation: blob 7s infinite;
            }
            .animation-delay-2000 {
                animation-delay: 2s;
            }
            .animation-delay-4000 {
                animation-delay: 4s;
            }
        </style>
    </body>
</html>