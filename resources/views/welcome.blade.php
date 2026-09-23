<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Gestion des Stagiaires') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            font-family: 'Figtree', sans-serif;
        }

        /* Hero avec dégradé animé + motif (utilisé pour le bandeau CTA, sans photo) */
        .bg-hero {
            background:
                radial-gradient(circle at 20% 20%, rgba(99, 102, 241, 0.55), transparent 45%),
                radial-gradient(circle at 80% 0%, rgba(168, 85, 247, 0.45), transparent 40%),
                linear-gradient(135deg, #1e1b4b 0%, #312e81 45%, #4c1d95 100%);
            position: relative;
            overflow: hidden;
        }
        .bg-hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(255,255,255,0.08) 1px, transparent 1px);
            background-size: 22px 22px;
            opacity: 0.5;
        }

        /* Hero principal : photo du bâtiment + dégradé en overlay */
        .bg-hero-photo {
            position: relative;
            overflow: hidden;
        }
        .bg-hero-photo::after {
            content: "";
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 20% 20%, rgba(99, 102, 241, 0.55), transparent 45%),
                radial-gradient(circle at 80% 0%, rgba(168, 85, 247, 0.45), transparent 40%),
                linear-gradient(135deg, rgba(30, 27, 75, 0.88) 0%, rgba(49, 46, 129, 0.82) 45%, rgba(76, 29, 149, 0.88) 100%);
        }
        .bg-hero-photo::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(255,255,255,0.08) 1px, transparent 1px);
            background-size: 22px 22px;
            opacity: 0.5;
            z-index: 1;
        }

        .fade-up {
            animation: fadeUp 0.8s ease both;
        }
        .fade-up-delay-1 { animation-delay: .15s; }
        .fade-up-delay-2 { animation-delay: .3s; }
        .fade-up-delay-3 { animation-delay: .45s; }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .glass {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
    </style>
</head>
<body class="antialiased bg-gray-50 dark:bg-gray-900">
    <div class="relative min-h-screen">

        <!-- Barre de navigation -->
        <header class="absolute top-0 inset-x-0 z-20">
            <div class="max-w-7xl mx-auto px-6 py-6 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/15 backdrop-blur flex items-center justify-center overflow-hidden">
                        <img src="{{ asset('images/logo-ecole.png') }}" alt="Logo" class="w-full h-full object-cover">
                    </div>
                    <span class="text-white font-semibold tracking-wide hidden sm:block">
                        {{ config('app.name', 'Gestion des Stagiaires') }}
                    </span>
                </div>

                @if (Route::has('login'))
                    <div>
                        @auth
                            <a href="{{ route('dashboard') }}"
                               class="inline-flex items-center gap-2 text-sm font-semibold text-white glass px-4 py-2.5 rounded-lg hover:bg-white/15 transition-all">
                                <i class="fas fa-gauge"></i> Tableau de bord
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                               class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-700 bg-white px-4 py-2.5 rounded-lg shadow-lg hover:bg-indigo-50 transition-all">
                                <i class="fas fa-arrow-right-to-bracket"></i> Connectez-vous
                            </a>
                        @endauth
                    </div>
                @endif
            </div>
        </header>

        <!-- Section Hero -->
        <div class="bg-hero-photo pt-32 pb-28 text-center text-white">
            <img src="{{ asset('images/campus-ecole.jpg') }}" alt="Bâtiment de l'établissement"
                 class="absolute inset-0 w-full h-full object-cover">

            <div class="relative z-10 max-w-4xl mx-auto px-6">

                <img src="{{ asset('images/logo-ecole.png') }}" alt="Logo Établissement"
                     class="fade-up mx-auto mb-6 w-24 h-24 rounded-full shadow-lg bg-white p-2 object-contain">

                <div class="fade-up inline-flex items-center gap-2 text-xs font-medium tracking-wide uppercase text-indigo-100 bg-white/10 border border-white/15 rounded-full px-4 py-1.5 mb-8">
                    <i class="fas fa-sparkles text-indigo-200"></i>
                    Plateforme de gestion scolaire
                </div>

                <h1 class="fade-up fade-up-delay-1 text-4xl md:text-6xl font-extrabold mb-6 leading-tight">
                    Bienvenue sur la plateforme de<br class="hidden md:block">
                    gestion de votre établissement
                </h1>

                <p class="fade-up fade-up-delay-2 text-lg md:text-xl text-indigo-100/90 mb-10 max-w-2xl mx-auto">
                    Simplifiez la gestion des stagiaires, filières et résultats grâce à une interface moderne et intuitive.
                </p>

                <div class="fade-up fade-up-delay-3 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center gap-2 bg-white text-indigo-700 font-semibold py-3.5 px-8 rounded-xl shadow-xl hover:bg-indigo-50 hover:-translate-y-0.5 transition-all duration-200">
                        <i class="fas fa-lock"></i> Connectez-vous
                    </a>
                    <a href="#fonctionnalites"
                       class="inline-flex items-center gap-2 text-white font-semibold py-3.5 px-8 rounded-xl glass hover:bg-white/15 transition-all duration-200">
                        En savoir plus <i class="fas fa-arrow-down text-sm"></i>
                    </a>
                </div>
            </div>

            <!-- Vague de transition -->
            <svg class="absolute bottom-0 left-0 w-full text-gray-50 dark:text-gray-900" viewBox="0 0 1440 80" fill="currentColor" preserveAspectRatio="none">
                <path d="M0,32 C240,80 480,0 720,16 C960,32 1200,80 1440,48 L1440,80 L0,80 Z"></path>
            </svg>
        </div>

        <!-- Section Fonctionnalités -->
        <div id="fonctionnalites" class="max-w-7xl mx-auto px-6 py-20 lg:py-24">

            <div class="text-center max-w-2xl mx-auto mb-14">
                <span class="text-indigo-600 dark:text-indigo-400 font-semibold text-sm tracking-wide uppercase">Fonctionnalités</span>
                <h2 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mt-2">
                    Tout ce dont votre établissement a besoin
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8">

                <!-- Gestion des Stagiaires -->
                <div class="group p-8 bg-white dark:bg-gray-800 rounded-2xl shadow-sm hover:shadow-xl border border-gray-100 dark:border-gray-700 transition-all duration-300 hover:-translate-y-1">
                    <div class="h-14 w-14 bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center rounded-xl mb-6 group-hover:bg-indigo-600 transition-colors duration-300">
                        <i class="fas fa-users text-xl text-indigo-600 group-hover:text-white transition-colors duration-300"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Gestion des Stagiaires</h3>
                    <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed">
                        Ajoutez, modifiez ou exportez les informations des stagiaires de façon rapide et fiable.
                    </p>
                </div>

                <!-- Gestion des Filières -->
                <div class="group p-8 bg-white dark:bg-gray-800 rounded-2xl shadow-sm hover:shadow-xl border border-gray-100 dark:border-gray-700 transition-all duration-300 hover:-translate-y-1">
                    <div class="h-14 w-14 bg-purple-50 dark:bg-purple-500/10 flex items-center justify-center rounded-xl mb-6 group-hover:bg-purple-600 transition-colors duration-300">
                        <i class="fas fa-graduation-cap text-xl text-purple-600 group-hover:text-white transition-colors duration-300"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Gestion des Filières</h3>
                    <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed">
                        Organisez les filières, niveaux et classes de votre établissement efficacement.
                    </p>
                </div>

                <!-- Suivi des Notes -->
                <div class="group p-8 bg-white dark:bg-gray-800 rounded-2xl shadow-sm hover:shadow-xl border border-gray-100 dark:border-gray-700 transition-all duration-300 hover:-translate-y-1">
                    <div class="h-14 w-14 bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center rounded-xl mb-6 group-hover:bg-emerald-600 transition-colors duration-300">
                        <i class="fas fa-clipboard-list text-xl text-emerald-600 group-hover:text-white transition-colors duration-300"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Suivi des Notes</h3>
                    <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed">
                        Consultez et gérez les bulletins, moyennes et décisions du jury.
                    </p>
                </div>

            </div>
        </div>

        <!-- Bandeau d'appel à l'action -->
        <div class="max-w-5xl mx-auto px-6 pb-20">
            <div class="bg-hero rounded-3xl px-8 py-12 md:py-14 text-center text-white relative overflow-hidden">
                <div class="relative z-10">
                    <h3 class="text-2xl md:text-3xl font-bold mb-3">Prêt à commencer ?</h3>
                    <p class="text-indigo-100/90 mb-8 max-w-xl mx-auto">
                        Accédez à votre espace personnel et gérez votre établissement en toute simplicité.
                    </p>
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center gap-2 bg-white text-indigo-700 font-semibold py-3.5 px-8 rounded-xl shadow-xl hover:bg-indigo-50 hover:-translate-y-0.5 transition-all duration-200">
                        <i class="fas fa-arrow-right-to-bracket"></i> Se connecter
                    </a>
                </div>
            </div>
        </div>

        <!-- Pied de page -->
        <footer class="text-center py-8 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
            <div class="text-sm text-gray-500 dark:text-gray-400">
                &copy; {{ date('Y') }} <strong class="text-gray-700 dark:text-gray-200">{{ config('app.name', 'Gestion des Stagiaires') }}</strong> — Tous droits réservés.<br>
                Développé par <span class="font-semibold text-indigo-600">ALHASSANE DIANE</span>
            </div>
        </footer>

    </div>
</body>
</html>