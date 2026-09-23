<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Espace Comptable') - {{ config('app.name') }}</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --sidebar-width: 260px;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: #f8fafc;
            overflow-x: hidden;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #1e40af 0%, #1e3a8a 100%);
            box-shadow: 4px 0 15px rgba(0,0,0,0.1);
            z-index: 1000;
            overflow-y: auto;
            transition: transform 0.3s ease;
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 3px;
        }

        .sidebar-header {
            padding: 1.5rem;
            background: rgba(0,0,0,0.2);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-brand {
            color: white;
            font-size: 1.25rem;
            font-weight: 700;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .sidebar-brand i {
            font-size: 1.5rem;
        }

        .sidebar-menu {
            padding: 1rem 0;
        }

        .menu-section-title {
            color: rgba(255,255,255,0.5);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1rem 1.5rem 0.5rem;
        }

        .menu-item {
            padding: 0.75rem 1.5rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s;
            position: relative;
        }

        .menu-item i {
            width: 20px;
            font-size: 1.1rem;
        }

        .menu-item:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .menu-item.active {
            background: rgba(255,255,255,0.15);
            color: white;
            font-weight: 600;
        }

        .menu-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: #fbbf24;
        }

        .menu-badge {
            margin-left: auto;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Top Navbar */
        .top-navbar {
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .navbar-search {
            flex: 1;
            max-width: 500px;
        }

        .navbar-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .navbar-icon-btn {
            position: relative;
            background: #f1f5f9;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .navbar-icon-btn:hover {
            background: #e2e8f0;
        }

        .notification-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: #ef4444;
            color: white;
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .user-dropdown {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: background 0.2s;
        }

        .user-dropdown:hover {
            background: #f1f5f9;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        /* Content Area */
        .content-wrapper {
            flex: 1;
            padding: 2rem;
        }

        /* Cards */
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            transition: all 0.3s;
            border: 1px solid #e2e8f0;
        }

        .stat-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
            transform: translateY(-2px);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .mobile-menu-toggle {
                display: block !important;
            }
        }

        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
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

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.5s ease-out;
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="{{ route('comptable.dashboard') }}" class="sidebar-brand">
                <i class="fas fa-calculator"></i>
                <span>Comptabilité</span>
            </a>
        </div>

        <nav class="sidebar-menu">
            <!-- Dashboard -->
            <a href="{{ route('comptable.dashboard') }}" class="menu-item {{ request()->routeIs('comptable.dashboard') ? 'active' : '' }}">
                <i class="fas fa-chart-pie"></i>
                <span>Tableau de bord</span>
            </a>

            <!-- Section Paiements -->
            <div class="menu-section-title">Paiements</div>
            
            <a href="{{ route('paiements.index') }}" class="menu-item {{ request()->routeIs('paiements.*') ? 'active' : '' }}">
                <i class="fas fa-money-bill-wave"></i>
                <span>Tous les paiements</span>
            </a>

            <a href="{{ route('paiements.index', ['statut' => 'en_attente']) }}" class="menu-item">
                <i class="fas fa-clock"></i>
                <span>En attente</span>
                <span class="menu-badge bg-warning text-dark" id="paiements-attente-count">0</span>
            </a>

            <a href="{{ route('paiements.create') }}" class="menu-item">
                <i class="fas fa-plus-circle"></i>
                <span>Nouveau paiement</span>
            </a>

            <!-- Section Échéanciers -->
            <div class="menu-section-title">Échéanciers</div>
            
            <a href="{{ route('echeanciers.index') }}" class="menu-item {{ request()->routeIs('echeanciers.*') ? 'active' : '' }}">
                <i class="fas fa-calendar-alt"></i>
                <span>Tous les échéanciers</span>
            </a>

            <a href="{{ route('echeanciers.index', ['en_retard' => true]) }}" class="menu-item">
                <i class="fas fa-exclamation-triangle"></i>
                <span>En retard</span>
                <span class="menu-badge bg-danger" id="retards-count">0</span>
            </a>

            <a href="{{ route('echeanciers.create') }}" class="menu-item">
                <i class="fas fa-plus-circle"></i>
                <span>Nouvel échéancier</span>
            </a>

            <!-- Section Gestion -->
            <div class="menu-section-title">Gestion</div>
            
            <a href="{{ route('comptable.stagiaires') }}" class="menu-item {{ request()->routeIs('comptable.stagiaires*') ? 'active' : '' }}">
                <i class="fas fa-users"></i>
                <span>Stagiaires</span>
            </a>

            <a href="{{ route('remises.index') }}" class="menu-item {{ request()->routeIs('remises.*') ? 'active' : '' }}">
                <i class="fas fa-percentage"></i>
                <span>Remises</span>
            </a>

            <!-- Section Rapports -->
            <div class="menu-section-title">Rapports</div>
            
            <a href="{{ route('comptable.rapports') }}" class="menu-item {{ request()->routeIs('comptable.rapports') ? 'active' : '' }}">
                <i class="fas fa-chart-line"></i>
                <span>Rapports financiers</span>
            </a>

            <a href="{{ route('admin.rapports.financier') }}" class="menu-item">
                <i class="fas fa-file-export"></i>
                <span>Exports</span>
            </a>
        </nav>
    </aside>

    <!-- Overlay pour mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Navbar -->
        <nav class="top-navbar">
            <div class="d-flex align-items-center gap-3">
                <button class="mobile-menu-toggle" id="mobileMenuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                
                <div class="navbar-search">
                    <form action="{{ route('comptable.stagiaires') }}" method="GET">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" name="search" class="form-control border-start-0" 
                                   placeholder="Rechercher un stagiaire..." 
                                   value="{{ request('search') }}">
                        </div>
                    </form>
                </div>
            </div>

            <div class="navbar-actions">
                <!-- Notifications -->
                <div class="dropdown">
                    <button class="navbar-icon-btn" data-bs-toggle="dropdown">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge" id="notif-count" style="display: none;">0</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" style="width: 320px;">
                        <div class="p-3 border-bottom">
                            <h6 class="mb-0">Notifications</h6>
                        </div>
                        <div id="notifications-list" style="max-height: 400px; overflow-y: auto;">
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-bell-slash fa-2x mb-2"></i>
                                <p class="mb-0">Aucune notification</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Messages -->
                <div class="dropdown">
                    <button class="navbar-icon-btn" data-bs-toggle="dropdown">
                        <i class="fas fa-envelope"></i>
                        <span class="notification-badge" id="messages-count" style="display: none;">0</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" style="width: 320px;">
                        <div class="p-3 border-bottom">
                            <h6 class="mb-0">Messages</h6>
                        </div>
                        <div class="text-center py-4">
                            <a href="{{ route('messages.index') }}" class="btn btn-sm btn-primary">
                                Voir tous les messages
                            </a>
                        </div>
                    </div>
                </div>

                <!-- User Menu -->
                <div class="dropdown">
                    <div class="user-dropdown" data-bs-toggle="dropdown">
                        <div class="user-avatar">
                            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                        </div>
                        <div class="d-none d-md-block">
                            <div class="fw-semibold">{{ auth()->user()->name }}</div>
                            <small class="text-muted">Comptable</small>
                        </div>
                        <i class="fas fa-chevron-down text-muted"></i>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                <i class="fas fa-user me-2"></i>Mon profil
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Content -->
        <div class="content-wrapper">
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show animate-fade-in-up" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show animate-fade-in-up" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show animate-fade-in-up" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Erreurs de validation :</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @yield('content')
        </div>
    </main>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        // Mobile menu toggle
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        if (mobileMenuToggle) {
            mobileMenuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('show');
                sidebarOverlay.classList.toggle('show');
            });

            sidebarOverlay.addEventListener('click', () => {
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            });
        }

        // Charger les compteurs
        function loadCounters() {
            // Compteur paiements en attente
            fetch('{{ route("paiements.index") }}?statut=en_attente&ajax=1')
                .then(r => r.json())
                .then(data => {
                    if (data.count > 0) {
                        document.getElementById('paiements-attente-count').textContent = data.count;
                    }
                })
                .catch(() => {});

            // Compteur retards
            fetch('{{ route("echeanciers.index") }}?en_retard=1&ajax=1')
                .then(r => r.json())
                .then(data => {
                    if (data.count > 0) {
                        document.getElementById('retards-count').textContent = data.count;
                    }
                })
                .catch(() => {});

            // Notifications
            fetch('{{ route("notifications.unread-count") }}')
                .then(r => r.json())
                .then(data => {
                    if (data.count > 0) {
                        const badge = document.getElementById('notif-count');
                        badge.textContent = data.count;
                        badge.style.display = 'block';
                    }
                })
                .catch(() => {});

            // Messages
            fetch('{{ route("messages.unread-count") }}')
                .then(r => r.json())
                .then(data => {
                    if (data.count > 0) {
                        const badge = document.getElementById('messages-count');
                        badge.textContent = data.count;
                        badge.style.display = 'block';
                    }
                })
                .catch(() => {});
        }

        // Charger au démarrage
        loadCounters();
        
        // Recharger toutes les 30 secondes
        setInterval(loadCounters, 30000);

        // Select2 initialization
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
        });

        // Auto-hide alerts
        setTimeout(() => {
            $('.alert').fadeOut('slow');
        }, 5000);
    </script>

    @stack('scripts')
</body>
</html>