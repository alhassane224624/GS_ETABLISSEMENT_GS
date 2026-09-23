<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Tableau de Bord - EMSI')</title>

    <!-- Google Fonts + Bootstrap + FontAwesome -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --violet: #4f46e5;
            --violet-light: #6366f1;
            --gris-clair: #f8fafc;
            --sidebar-width: 260px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--gris-clair);
            overflow-x: hidden;
        }

        /* === SIDEBAR === */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(160deg, var(--violet), var(--violet-light));
            color: #fff;
            overflow-y: auto;
            box-shadow: 3px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
            transition: transform 0.3s ease;
        }

        .sidebar h4 {
            text-align: center;
            padding: 1.2rem 0;
            font-weight: 600;
            border-bottom: 1px solid rgba(255,255,255,0.15);
        }

        .sidebar small {
            display: block;
            text-align: center;
            color: rgba(255,255,255,0.7);
            margin-bottom: 1rem;
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.9);
            padding: 0.75rem 1.3rem;
            margin: 0.2rem 1rem;
            border-radius: 0.6rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            position: relative;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: #fff;
            transform: translateX(5px);
        }

        .sidebar .collapse .nav-link {
            padding-left: 2.3rem;
            padding-right: 3rem !important;
            font-size: 0.92rem;
            margin-bottom: 0.25rem;
        }

        .sidebar li { 
            margin-bottom: 0.6rem; 
        }

        .sidebar-footer {
            position: sticky;
            bottom: 0;
            background: rgba(255,255,255,0.1);
            padding: 1rem;
            border-top: 1px solid rgba(255,255,255,0.15);
        }

        /* Badge notifications */
        .nav-badge {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            right: 10px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border-radius: 12px;
            min-width: 22px;
            height: 22px;
            padding: 0 6px;
            font-size: 11px;
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
                box-shadow: 0 2px 6px rgba(239, 68, 68, 0.4);
            }
            50% {
                transform: translateY(-50%) scale(1.1);
                box-shadow: 0 3px 8px rgba(239, 68, 68, 0.6);
            }
        }

        .badge-warning-custom {
            background: linear-gradient(135deg, #f59e0b, #d97706) !important;
            box-shadow: 0 2px 6px rgba(245, 158, 11, 0.4);
        }

        .nav-link:hover .nav-badge {
            transform: translateY(-50%) scale(1.15);
            box-shadow: 0 3px 10px rgba(239, 68, 68, 0.6);
        }

        .nav-badge.large-number {
            font-size: 9px;
            padding: 0 4px;
        }

        /* === MAIN CONTENT === */
        main {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            padding: 2rem;
            width: calc(100% - var(--sidebar-width));
            transition: margin-left 0.3s ease, width 0.3s ease;
        }

        .navbar {
            background: #fff !important;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            margin-bottom: 1.8rem;
        }

        .navbar h5 {
            color: var(--violet);
            font-weight: 600;
        }

        .alert {
            border-radius: 10px;
            margin-bottom: 1.2rem;
        }

        /* Dropdown notifications */
        .notification-dropdown {
            width: 380px;
            max-height: 450px;
            overflow-y: auto;
            border: none;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        }

        .notification-item {
            transition: all 0.2s;
            border-left: 3px solid transparent;
            cursor: pointer;
        }

        .notification-item:hover {
            background-color: #f8f9fa;
            transform: translateX(2px);
        }

        .notification-item.unread {
            background-color: #e7f3ff;
            border-left-color: var(--violet);
        }

        .notification-icon {
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            flex-shrink: 0;
            font-size: 1.1rem;
        }

        .notification-icon.success { background-color: #d1f2eb; color: #198754; }
        .notification-icon.info { background-color: #cfe2ff; color: #0d6efd; }
        .notification-icon.warning { background-color: #fff3cd; color: #ffc107; }
        .notification-icon.danger { background-color: #f8d7da; color: #dc3545; }

        .badge-notification {
            position: absolute;
            top: -5px;
            right: -8px;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Toggle button pour mobile */
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1100;
            background: var(--violet);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        /* === RESPONSIVE === */
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

            .sidebar-toggle {
                display: block;
            }

            .notification-dropdown {
                width: 320px;
            }

            .navbar {
                margin-top: 3rem;
            }
        }

        @media (max-width: 576px) {
            main {
                padding: 0.5rem;
            }

            .notification-dropdown {
                width: 280px;
            }

            :root {
                --sidebar-width: 280px;
            }
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
    </style>

    @stack('styles')
</head>

<body>
    <!-- Overlay pour mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Toggle button pour mobile -->
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- === SIDEBAR === -->
    <nav class="sidebar" id="sidebar">
        <h4><i class="fa-solid fa-school me-2"></i> GS</h4>
        <small><i class="fa fa-user-circle me-1"></i> {{ Auth::user()->name }}</small>

        <ul class="nav flex-column">
            <!-- Tableau de bord -->
            <li class="nav-item">
                <a href="{{ route('dashboard') }}" class="nav-link {{ Request::is('dashboard') ? 'active' : '' }}">
                    <i class="fa fa-home me-2"></i> Tableau de bord
                </a>
            </li>

            <!-- Messagerie avec notification -->
            <li class="nav-item">
                <a href="{{ route('messages.index') }}" 
                   class="nav-link {{ Request::is('messages*') ? 'active' : '' }}"
                   style="position: relative;">
                    <i class="fa fa-envelope me-2"></i> Messages
                    
                    @php
                        $unreadMessages = Auth::user()->getUnreadMessagesCount();
                    @endphp
                    @if($unreadMessages > 0)
                        <span class="nav-badge" id="messageBadge">{{ $unreadMessages }}</span>
                    @endif
                </a>
            </li>

            <!-- Notifications -->
            <li class="nav-item">
                <a href="{{ route('notifications.index') }}" 
                   class="nav-link {{ Request::is('notifications*') ? 'active' : '' }}"
                   style="position: relative;">
                    <i class="fa fa-bell me-2"></i> Notifications
                    
                    <span class="nav-badge" id="notificationBadgeSidebar" style="display: none;">0</span>
                </a>
            </li>

            <!-- Gestion -->
            <li>
                <a class="nav-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#menuGestion">
                    <span><i class="fa fa-layer-group me-2"></i> Gestion</span>
                    <i class="fa fa-chevron-down small"></i>
                </a>
                <div class="collapse" id="menuGestion">
                    <a href="{{ route('stagiaires.index') }}" class="nav-link"><i class="fa fa-user-graduate me-2"></i> Stagiaires</a>
                    <a href="{{ route('users.index') }}" class="nav-link"><i class="fa fa-users me-2"></i> Utilisateurs</a>
                    <a href="{{ route('filieres.index') }}" class="nav-link"><i class="fa fa-sitemap me-2"></i> Filières</a>
                    <a href="{{ route('niveaux.index') }}" class="nav-link {{ request()->is('niveaux*') ? 'active' : '' }}"><i class="fas fa-layer-group me-2"></i> Niveaux</a>
                    <a href="{{ route('classes.index') }}" class="nav-link"><i class="fa fa-building me-2"></i> Classes</a>
                    <a href="{{ route('matieres.index') }}" class="nav-link"><i class="fa fa-book me-2"></i> Matières</a>
                </div>
            </li>

            <!-- Finances -->
            <li>
                <a class="nav-link d-flex justify-content-between align-items-center" 
                   data-bs-toggle="collapse" href="#menuFinances">
                    <span><i class="fa fa-wallet me-2"></i> Finances</span>
                    <i class="fa fa-chevron-down small"></i>
                </a>
                <div class="collapse" id="menuFinances">
                    <a href="{{ route('admin.rapports.financier') }}" 
                       class="nav-link {{ Request::is('admin/rapports/financier*') ? 'active' : '' }}">
                        <i class="fa fa-chart-line me-2"></i> Dashboard Financier
                    </a>
                    
                    <a href="{{ route('paiements.index') }}" 
                       class="nav-link {{ Request::is('paiements*') ? 'active' : '' }}">
                        <i class="fa fa-money-bill-wave me-2"></i> Paiements
                    </a>
                    <a href="{{ route('echeanciers.index') }}" 
                       class="nav-link {{ Request::is('echeanciers*') ? 'active' : '' }}">
                        <i class="fa fa-calendar-check me-2"></i> Échéanciers
                    </a>
                    <a href="{{ route('remises.index') }}" 
                       class="nav-link {{ Request::is('remises*') ? 'active' : '' }}">
                        <i class="fa fa-percent me-2"></i> Remises
                    </a>
                </div>
            </li>

            <!-- Pédagogie -->
            <li>
                <a class="nav-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#menuPedagogie">
                    <span><i class="fa fa-chalkboard-teacher me-2"></i> Pédagogie</span>
                    <i class="fa fa-chevron-down small"></i>
                </a>
                <div class="collapse" id="menuPedagogie">
                    <a href="{{ route('notes.index') }}" class="nav-link">
                        <i class="fa fa-clipboard-list me-2"></i> Notes
                    </a>
                    <a href="{{ route('absences.index') }}" class="nav-link">
                        <i class="fa fa-calendar-xmark me-2"></i> Absences
                    </a>
                    <a href="{{ route('planning.index') }}" class="nav-link">
                        <i class="fa fa-calendar-days me-2"></i> Planning
                    </a>
                    
                    @php
                        $pendingBulletins = \App\Models\Bulletin::whereNull('validated_at')->count();
                    @endphp
                    
                    <a href="{{ route('bulletins.index') }}" 
                       class="nav-link {{ request()->routeIs('bulletins.index', 'bulletins.show') ? 'active' : '' }}" 
                       style="position: relative;">
                        <i class="fa fa-file-lines me-2"></i> Bulletins
                        
                        @if($pendingBulletins > 0)
                            <span class="nav-badge" id="bulletinBadge">{{ $pendingBulletins }}</span>
                        @endif
                    </a>
                    
                    @if($pendingBulletins > 0)
                        <a href="{{ route('bulletins.pending') }}" 
                           class="nav-link {{ request()->routeIs('bulletins.pending') ? 'active' : '' }}"
                           style="position: relative;"
                           id="bulletinPendingLink">
                            <i class="fas fa-clock me-2"></i> <span style="color: #fbbf24;">À valider</span>
                            <span class="nav-badge badge-warning-custom">{{ $pendingBulletins }}</span>
                        </a>
                    @else
                        <a href="{{ route('bulletins.pending') }}" 
                           class="nav-link"
                           style="position: relative; display: none;"
                           id="bulletinPendingLink">
                            <i class="fas fa-clock me-2"></i> <span style="color: #fbbf24;">À valider</span>
                            <span class="nav-badge badge-warning-custom">0</span>
                        </a>
                    @endif
                </div>
            </li>

            <!-- Paramètres -->
            <li>
                <a class="nav-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#menuParametres">
                    <span><i class="fa fa-cog me-2"></i> Paramètres</span>
                    <i class="fa fa-chevron-down small"></i>
                </a>
                <div class="collapse" id="menuParametres">
                    <a href="{{ route('annees-scolaires.index') }}" class="nav-link"><i class="fa fa-calendar me-2"></i> Années scolaires</a>
                    <a href="{{ route('periodes.index') }}" class="nav-link"><i class="fa fa-clock me-2"></i> Périodes</a>
                    <a href="{{ route('salles.index') }}" class="nav-link"><i class="fa fa-door-open me-2"></i> Salles</a>
                    <a href="{{ route('statistics.index') }}" class="nav-link"><i class="fa fa-chart-bar me-2"></i> Statistiques</a>
                </div>
            </li>
        </ul>

        <!-- Déconnexion -->
        <div class="sidebar-footer">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-outline-light w-100">
                    <i class="fa fa-sign-out-alt me-2"></i> Déconnexion
                </button>
            </form>
        </div>
    </nav>

    <!-- === CONTENU PRINCIPAL === -->
    <main>
        <nav class="navbar px-3 py-2 d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="mb-0">@yield('page-title', 'Tableau de bord Administrateur')</h5>
            
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <!-- Dropdown Notifications -->
                <div class="dropdown">
                    <a class="position-relative text-decoration-none" href="#" 
                       id="notificationDropdown" role="button" 
                       data-bs-toggle="dropdown" aria-expanded="false"
                       style="cursor: pointer;">
                        <i class="fas fa-bell fa-lg" style="color: var(--violet);"></i>
                        <span class="badge bg-danger badge-notification" 
                              id="notificationBadgeNavbar" style="display: none;">0</span>
                    </a>
                    
                    <div class="dropdown-menu dropdown-menu-end notification-dropdown p-0 shadow-lg rounded-3" 
                         aria-labelledby="notificationDropdown">
                        <div class="d-flex justify-content-between align-items-center p-3 border-bottom" 
                             style="background: linear-gradient(135deg, var(--violet), var(--violet-light));">
                            <h6 class="mb-0 fw-bold text-white">
                                <i class="fas fa-bell me-2"></i>Notifications
                            </h6>
                            <button class="btn btn-sm btn-light" 
                                    onclick="markAllAsRead()" 
                                    title="Tout marquer comme lu">
                                <i class="fas fa-check-double"></i>
                            </button>
                        </div>
                        
                        <div id="notificationList">
                            <div class="text-center py-5">
                                <i class="fas fa-spinner fa-spin text-muted fa-2x"></i>
                                <p class="text-muted mt-2 mb-0">Chargement...</p>
                            </div>
                        </div>
                        
                        <div class="text-center p-2 border-top bg-light">
                            <a href="{{ route('notifications.index') }}" 
                               class="btn btn-sm btn-link text-decoration-none fw-semibold" 
                               style="color: var(--violet);">
                                <i class="fas fa-list me-1"></i>Voir toutes les notifications
                            </a>
                        </div>
                    </div>
                </div>

                <span class="badge bg-primary">{{ Auth::user()->role }}</span>
                <span class="d-none d-md-inline"><i class="fa fa-user me-1"></i> {{ Auth::user()->name }}</span>
            </div>
        </nav>

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Toggle sidebar sur mobile
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        });

        // Fermer sidebar quand on clique sur l'overlay
        document.getElementById('sidebarOverlay')?.addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });

        // Chargement initial
        document.addEventListener('DOMContentLoaded', function() {
            loadNotifications();
            updateNotificationCount();
            updateBulletinCount();
            
            setInterval(() => {
                updateNotificationCount();
                updateMessageCount();
                updateBulletinCount();
            }, 30000);
            
            document.getElementById('notificationDropdown')?.addEventListener('click', function() {
                loadNotifications();
            });
        });

        // FONCTIONS NOTIFICATIONS
        async function loadNotifications() {
            try {
                const response = await fetch('{{ route("notifications.recent") }}');
                const data = await response.json();
                
                const listElement = document.getElementById('notificationList');
                
                if (data.notifications.length === 0) {
                    listElement.innerHTML = `
                        <div class="text-center py-5">
                            <i class="fas fa-bell-slash text-muted fa-3x mb-3" style="opacity: 0.3;"></i>
                            <p class="text-muted mb-0">Aucune notification</p>
                        </div>
                    `;
                    return;
                }
                
                listElement.innerHTML = data.notifications.map(notification => `
                    <div class="notification-item ${notification.read_at ? '' : 'unread'} p-3 border-bottom" 
                         onclick="handleNotificationClick('${notification.id}', '${notification.url || ''}')">
                        <div class="d-flex gap-3 align-items-start">
                            <div class="notification-icon ${notification.type}">
                                <i class="${notification.icon}"></i>
                            </div>
                            <div class="flex-grow-1" style="min-width: 0;">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <h6 class="mb-0 fw-semibold" style="font-size: 0.9rem;">
                                        ${notification.title}
                                    </h6>
                                    ${!notification.read_at ? '<span class="badge bg-primary" style="font-size: 9px;">Nouveau</span>' : ''}
                                </div>
                                <p class="mb-2 small text-muted" style="font-size: 0.85rem; line-height: 1.4;">
                                    ${notification.message}
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted" style="font-size: 0.75rem;">
                                        <i class="far fa-clock me-1"></i>${notification.created_at}
                                    </small>
                                    <button class="btn btn-sm btn-link text-danger p-0" 
                                            onclick="event.stopPropagation(); deleteNotification('${notification.id}')"
                                            title="Supprimer"
                                            style="font-size: 0.85rem;">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('');
                
            } catch (error) {
                console.error('Erreur chargement notifications:', error);
            }
        }

        async function updateNotificationCount() {
            try {
                const response = await fetch('{{ route("notifications.unread-count") }}');
                const data = await response.json();
                
                const badgeNavbar = document.getElementById('notificationBadgeNavbar');
                const badgeSidebar = document.getElementById('notificationBadgeSidebar');
                
                if (data.count > 0) {
                    const displayCount = data.count > 99 ? '99+' : data.count;
                    
                    if (badgeNavbar) {
                        badgeNavbar.textContent = displayCount;
                        badgeNavbar.style.display = 'flex';
                    }
                    
                    if (badgeSidebar) {
                        badgeSidebar.textContent = displayCount;
                        badgeSidebar.style.display = 'flex';
                    }
                } else {
                    if (badgeNavbar) badgeNavbar.style.display = 'none';
                    if (badgeSidebar) badgeSidebar.style.display = 'none';
                }
            } catch (error) {
                console.error('Erreur compteur notifications:', error);
            }
        }

        async function handleNotificationClick(notificationId, url) {
            try {
                await fetch(`/notifications/${notificationId}/read`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                
                updateNotificationCount();
                loadNotifications();
                
                if (url && url !== '' && url !== '#') {
                    window.location.href = url;
                }
            } catch (error) {
                console.error('Erreur clic notification:', error);
            }
        }

        async function markAllAsRead() {
            try {
                const response = await fetch('{{ route("notifications.read-all") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                
                if (response.ok) {
                    loadNotifications();
                    updateNotificationCount();
                    showToast('Toutes les notifications ont été marquées comme lues', 'success');
                }
            } catch (error) {
                console.error('Erreur marquage notifications:', error);
            }
        }

        async function deleteNotification(notificationId) {
            try {
                const response = await fetch(`/notifications/${notificationId}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrfToken }
                });
                
                if (response.ok) {
                    loadNotifications();
                    updateNotificationCount();
                    showToast('Notification supprimée', 'success');
                }
            } catch (error) {
                console.error('Erreur suppression:', error);
            }
        }

        async function updateMessageCount() {
            try {
                const response = await fetch('{{ route("messages.unread-count") }}');
                const data = await response.json();
                
                const badge = document.getElementById('messageBadge');
                
                if (data.count > 0) {
                    if (!badge) {
                        const link = document.querySelector('a[href*="messages"]');
                        const newBadge = document.createElement('span');
                        newBadge.id = 'messageBadge';
                        newBadge.className = 'nav-badge';
                        newBadge.textContent = data.count;
                        link.appendChild(newBadge);
                    } else {
                        badge.textContent = data.count;
                        badge.style.display = 'flex';
                    }
                } else if (badge) {
                    badge.style.display = 'none';
                }
            } catch (error) {
                console.error('Erreur compteur messages:', error);
            }
        }

        async function updateBulletinCount() {
            try {
                const response = await fetch('{{ route("bulletins.pending-count") }}');
                const data = await response.json();
                
                const bulletinBadge = document.getElementById('bulletinBadge');
                const pendingLink = document.getElementById('bulletinPendingLink');
                
                if (data.count > 0) {
                    const displayCount = data.count > 99 ? '99+' : data.count;
                    
                    if (bulletinBadge) {
                        bulletinBadge.textContent = displayCount;
                        bulletinBadge.style.display = 'flex';
                        
                        if (data.count > 99) {
                            bulletinBadge.classList.add('large-number');
                        } else {
                            bulletinBadge.classList.remove('large-number');
                        }
                    }
                    
                    if (pendingLink) {
                        pendingLink.style.display = 'block';
                        const pendingBadge = pendingLink.querySelector('.nav-badge');
                        if (pendingBadge) {
                            pendingBadge.textContent = displayCount;
                        }
                    }
                } else {
                    if (bulletinBadge) {
                        bulletinBadge.style.display = 'none';
                    }
                    
                    if (pendingLink) {
                        pendingLink.style.display = 'none';
                    }
                }
                
            } catch (error) {
                console.error('Erreur compteur bulletins:', error);
            }
        }

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