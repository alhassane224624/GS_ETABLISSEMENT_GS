<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Espace Professeur')</title>

    <!-- Bootstrap / FontAwesome / Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>

    <style>
        :root {
            --primary: #8b5cf6;
            --secondary: #64748b;
            --danger: #ef4444;
            --sidebar-width: 250px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            overflow-x: hidden;
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(135deg, var(--primary), #a78bfa);
            box-shadow: 2px 0 10px rgba(0,0,0,0.08);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow-y: auto;
            z-index: 1000;
            transition: transform 0.3s ease;
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.85);
            padding: 0.7rem 1.25rem;
            border-radius: 0.5rem;
            margin: 0.2rem 0.5rem;
            transition: all 0.3s;
            white-space: nowrap;
            position: relative;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.15);
            color: #fff;
        }

        .sidebar small {
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 1px;
            opacity: 0.8;
        }

        .sidebar-footer {
            padding: 1rem;
            border-top: 1px solid rgba(255,255,255,0.2);
            background: rgba(255,255,255,0.05);
        }

        /* Badge notifications */
        .badge-notification {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            right: 10px;
            background: var(--danger);
            color: white;
            border-radius: 12px;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            font-size: 10px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 6px rgba(239, 68, 68, 0.4);
            animation: pulse-badge 2s infinite;
        }

        @keyframes pulse-badge {
            0%, 100% {
                transform: translateY(-50%) scale(1);
            }
            50% {
                transform: translateY(-50%) scale(1.1);
            }
        }

        /* ===== CONTENU PRINCIPAL ===== */
        main {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            padding: 20px;
            width: calc(100% - var(--sidebar-width));
            overflow-x: hidden;
            transition: margin-left 0.3s ease, width 0.3s ease;
        }

        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .navbar {
            border-radius: 0.75rem;
            background-color: #ffffff !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }

        .btn-primary {
            background: var(--primary);
            border: none;
        }

        .btn-primary:hover {
            background: #7c3aed;
        }

        /* Toggle button pour mobile */
        .toggle-sidebar-btn {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1100;
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        /* Overlay pour mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }

        .sidebar-overlay.show {
            display: block;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            :root {
                --sidebar-width: 220px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            main {
                margin-left: 0;
                width: 100%;
                padding: 1rem;
            }

            .toggle-sidebar-btn {
                display: block;
            }

            .navbar {
                margin-top: 3.5rem;
            }

            :root {
                --sidebar-width: 250px;
            }
        }

        @media (max-width: 576px) {
            main {
                padding: 0.5rem;
            }

            .navbar h5 {
                font-size: 1rem;
            }
        }

        /* Select2 responsive */
        .select2-container {
            width: 100% !important;
        }
    </style>

    @stack('styles')
</head>
<body>

    <!-- Overlay pour mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Toggle button pour mobile -->
    <button class="toggle-sidebar-btn" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- SIDEBAR -->
    <nav class="sidebar" id="sidebar">
        <div>
            <div class="text-center py-4">
                <h4 class="text-white fw-bold mb-1">
                    <i class="fas fa-chalkboard-teacher"></i> Espace Prof
                </h4>
                <small class="text-white-50">{{ Auth::user()->name }}</small>
            </div>

            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link {{ Request::is('professeur/dashboard') ? 'active' : '' }}" href="{{ route('professeur.dashboard') }}">
                        <i class="fas fa-home me-2"></i> Accueil
                    </a>
                </li>

                <li class="nav-item mt-3">
                    <small class="text-white-50 px-3">MON ESPACE</small>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ Request::is('professeur/stagiaires*') ? 'active' : '' }}" href="{{ route('professeur.stagiaires') }}">
                        <i class="fas fa-user-graduate me-2"></i> Mes Stagiaires
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ Request::is('professeur/notes*') ? 'active' : '' }}" href="{{ route('professeur.notes-par-matiere') }}">
                        <i class="fas fa-clipboard-list me-2"></i> Mes Notes
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ Request::is('professeur/presences*') ? 'active' : '' }}" href="{{ route('professeur.presences') }}">
                        <i class="fas fa-check-circle me-2"></i> Présences
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ Request::is('professeur/planning*') ? 'active' : '' }}" href="{{ route('professeur.planning') }}">
                        <i class="fas fa-calendar-alt me-2"></i> Mon Planning
                    </a>
                </li>

                <li class="nav-item mt-3">
                    <small class="text-white-50 px-3">COMMUNICATION</small>
                </li>

                <li class="nav-item">
                    <a id="messages-link" class="nav-link {{ Request::is('messages*') ? 'active' : '' }}" href="{{ route('messages.index') }}">
                        <i class="fas fa-envelope me-2"></i> Messages
                        <span id="message-badge" class="badge-notification" style="display:none;">0</span>
                    </a>
                </li>

                <li class="nav-item mt-3">
                    <small class="text-white-50 px-3">MON COMPTE</small>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ Request::is('profile*') ? 'active' : '' }}" href="{{ route('profile.edit') }}">
                        <i class="fas fa-user-cog me-2"></i> Mon Profil
                    </a>
                </li>
            </ul>
        </div>

        <div class="sidebar-footer">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-outline-light btn-sm w-100">
                    <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
                </button>
            </form>
        </div>
    </nav>

    <!-- CONTENU -->
    <main>
        <nav class="navbar navbar-light bg-white shadow-sm px-3 d-flex flex-wrap justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 me-3">@yield('page-title', 'Tableau de bord')</h5>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <span class="badge bg-primary">Professeur</span>
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle"></i> 
                        <span class="d-none d-md-inline">{{ Auth::user()->name }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}">
                            <i class="fas fa-user-edit me-2"></i> Profil
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Alerts -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(function() {
            // ===== SIDEBAR MOBILE =====
            $('#sidebarToggle, #sidebarOverlay').on('click', function() {
                $('#sidebar').toggleClass('show');
                $('#sidebarOverlay').toggleClass('show');
            });

            // Fermer la sidebar quand on clique sur un lien (mobile)
            $('#sidebar .nav-link').on('click', function() {
                if (window.innerWidth <= 768) {
                    $('#sidebar').removeClass('show');
                    $('#sidebarOverlay').removeClass('show');
                }
            });

            // ===== SELECT2 =====
            $('.select2').select2({ 
                theme: 'bootstrap-5', 
                width: '100%' 
            });

            // ===== MESSAGES NON LUS =====
            function updateUnreadCount() {
                fetch('{{ route("messages.unread-count") }}')
                    .then(res => res.json())
                    .then(data => {
                        const badge = document.getElementById('message-badge');
                        if (data.count > 0) {
                            badge.textContent = data.count > 99 ? '99+' : data.count;
                            badge.style.display = 'flex';
                        } else {
                            badge.style.display = 'none';
                        }
                    })
                    .catch(error => console.error('Erreur compteur messages:', error));
            }

            // Lancer au chargement et rafraîchir toutes les 20 secondes
            updateUnreadCount();
            setInterval(updateUnreadCount, 20000);

            // ===== RESPONSIVE NAVBAR =====
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    $('#sidebar').removeClass('show');
                    $('#sidebarOverlay').removeClass('show');
                }
            });
        });

        // Toast notification helper
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            toast.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
    </script>

    @stack('scripts')
</body>
</html>