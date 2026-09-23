<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Espace Stagiaire')</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    :root {
      --primary: #3b82f6;
      --sidebar-width: 260px;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', system-ui, sans-serif;
      background-color: #f1f5f9;
      overflow-x: hidden;
    }

    /* ===== Sidebar ===== */
    .sidebar {
      position: fixed;
      top: 0;
      left: 0;
      width: var(--sidebar-width);
      height: 100vh;
      background: linear-gradient(135deg, var(--primary) 0%, #60a5fa 100%);
      box-shadow: 2px 0 12px rgba(0,0,0,0.1);
      color: white;
      display: flex;
      flex-direction: column;
      z-index: 1000;
      overflow-y: auto;
      transition: transform 0.3s ease;
    }

    .sidebar-header {
      padding: 1.5rem;
      text-align: center;
      border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    .sidebar .nav-link {
      color: rgba(255,255,255,0.9);
      padding: .75rem 1.25rem;
      margin: .25rem .75rem;
      border-radius: .5rem;
      display: flex;
      align-items: center;
      transition: all .3s;
      position: relative;
    }

    .sidebar .nav-link i {
      width: 20px;
      margin-right: 10px;
      text-align: center;
    }

    .sidebar .nav-link:hover {
      background: rgba(255,255,255,0.15);
      transform: translateX(5px);
    }

    .sidebar .nav-link.active {
      background: rgba(255,255,255,0.25);
      font-weight: 600;
    }

    .section-title {
      font-size: .75rem;
      letter-spacing: .5px;
      text-transform: uppercase;
      color: rgba(255,255,255,0.6);
      padding: .5rem 1.25rem;
      margin-top: 1rem;
      font-weight: 600;
    }

    .sidebar-footer {
      margin-top: auto;
      padding: 1rem;
      border-top: 1px solid rgba(255,255,255,0.1);
    }

    /* Badge notifications */
    .badge-notification {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      right: 10px;
      background: #ef4444;
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

    /* ===== Main ===== */
    main {
      margin-left: var(--sidebar-width);
      padding: 2rem;
      min-height: 100vh;
      width: calc(100% - var(--sidebar-width));
      background-color: #f8fafc;
      transition: margin-left 0.3s ease, width 0.3s ease;
    }

    .navbar {
      background: #fff;
      border-radius: .75rem;
      box-shadow: 0 1px 4px rgba(0,0,0,.1);
      padding: .75rem 1.25rem;
      margin-bottom: 1.5rem;
    }

    .card {
      border: none;
      border-radius: 1rem;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
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

    /* ===== Responsive ===== */
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
        --sidebar-width: 260px;
      }
    }

    @media (max-width: 576px) {
      main {
        padding: 0.5rem;
      }

      .navbar h5 {
        font-size: 1rem;
      }

      .sidebar-header h5 {
        font-size: 1.1rem;
      }
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

  <!-- Sidebar -->
  <nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <i class="fas fa-user-graduate fa-2x mb-2"></i>
      <h5 class="fw-bold mb-1">Espace Stagiaire</h5>
      <small class="text-white-50">{{ Auth::user()->name }}</small>
    </div>

    <ul class="nav flex-column mt-3">
      <li class="nav-item">
        <a href="{{ route('stagiaire.dashboard') }}" class="nav-link {{ Request::is('stagiaire/dashboard') ? 'active' : '' }}">
          <i class="fas fa-home"></i><span> Accueil</span>
        </a>
      </li>

      <div class="section-title">Scolarité</div>
      <li class="nav-item">
        <a href="{{ route('stagiaire.notes') }}" class="nav-link {{ Request::is('stagiaire/notes*') ? 'active' : '' }}">
          <i class="fas fa-chart-line"></i><span> Mes Notes</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="{{ route('stagiaire.bulletin') }}" class="nav-link {{ Request::is('stagiaire/bulletin*') ? 'active' : '' }}">
          <i class="fas fa-file-alt"></i><span> Mon Bulletin</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="{{ route('stagiaire.emploi-du-temps') }}" class="nav-link {{ Request::is('stagiaire/emploi-du-temps*') ? 'active' : '' }}">
          <i class="fas fa-calendar-alt"></i><span> Emploi du Temps</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="{{ route('stagiaire.absences') }}" class="nav-link {{ Request::is('stagiaire/absences*') ? 'active' : '' }}">
          <i class="fas fa-calendar-times"></i><span> Mes Absences</span>
        </a>
      </li>

      <div class="section-title">Communication</div>
      <li class="nav-item">
        <a href="{{ route('messages.index') }}" class="nav-link {{ Request::is('messages*') ? 'active' : '' }}">
          <i class="fas fa-envelope"></i><span> Messages</span>
          <span id="message-badge" class="badge-notification" style="display:none;">0</span>
        </a>
      </li>

      <div class="section-title">Mon compte</div>
      <li class="nav-item">
        <a href="{{ route('stagiaire.profil') }}" class="nav-link {{ Request::is('stagiaire/profil*') ? 'active' : '' }}">
          <i class="fas fa-user-circle"></i><span> Mon Profil</span>
        </a>
      </li>
    </ul>

    <div class="sidebar-footer">
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn btn-outline-light w-100">
          <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
        </button>
      </form>
    </div>
  </nav>

  <!-- Main Content -->
  <main>
    <nav class="navbar">
      <div class="container-fluid">
        <h5 class="mb-0 fw-bold">@yield('page-title', 'Dashboard')</h5>
        <div class="d-flex align-items-center gap-3 flex-wrap">
          <span class="badge bg-info">Stagiaire</span>
          <div class="dropdown">
            <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
              <i class="fas fa-user-circle"></i> 
              <span class="d-none d-md-inline">{{ Auth::user()->name }}</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="{{ route('stagiaire.profil') }}">
                <i class="fas fa-user-edit me-2"></i>Mon Profil
              </a></li>
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

    @if($errors->any())
      <div class="alert alert-danger alert-dismissible fade show">
        <strong><i class="fas fa-exclamation-triangle me-2"></i>Erreurs :</strong>
        <ul class="mb-0 mt-2">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @yield('content')
  </main>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // ===== SIDEBAR MOBILE =====
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (sidebarToggle && sidebar && sidebarOverlay) {
      // Toggle sidebar
      sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('show');
        sidebarOverlay.classList.toggle('show');
      });

      // Fermer avec overlay
      sidebarOverlay.addEventListener('click', function() {
        sidebar.classList.remove('show');
        sidebarOverlay.classList.remove('show');
      });

      // Fermer sidebar quand on clique sur un lien (mobile)
      const navLinks = sidebar.querySelectorAll('.nav-link');
      navLinks.forEach(link => {
        link.addEventListener('click', function() {
          if (window.innerWidth <= 768) {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
          }
        });
      });

      // Gérer le resize
      window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
          sidebar.classList.remove('show');
          sidebarOverlay.classList.remove('show');
        }
      });
    }

    // ===== MESSAGES NON LUS =====
    function updateUnreadCount() {
      fetch('{{ route("messages.unread-count") }}')
        .then(response => response.json())
        .then(data => {
          const badge = document.getElementById('message-badge');
          if (badge) {
            if (data.count > 0) {
              badge.textContent = data.count > 99 ? '99+' : data.count;
              badge.style.display = 'flex';
            } else {
              badge.style.display = 'none';
            }
          }
        })
        .catch(error => console.error('Erreur compteur messages:', error));
    }

    // Lancer au chargement
    document.addEventListener('DOMContentLoaded', function() {
      updateUnreadCount();
      // Rafraîchir toutes les 20 secondes
      setInterval(updateUnreadCount, 20000);
    });

    // ===== TOAST HELPER =====
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