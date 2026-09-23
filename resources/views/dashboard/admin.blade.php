@extends('layouts.app')

@section('title', 'Dashboard Administrateur')
@section('page-title', 'Tableau de bord')

@push('styles')
<style>
/* === CSS Variables for Maintainability === */
:root {
    --primary-color: #3b82f6;
    --secondary-color: #6366f1;
    --success-color: #10b981;
    --danger-color: #ef4444;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --bg-light: rgba(255,255,255,0.85);
    --bg-gradient: linear-gradient(135deg, #f9fafb, #eef2ff);
    --shadow-sm: 0 6px 20px rgba(0,0,0,0.05);
    --shadow-md: 0 12px 35px rgba(0,0,0,0.1);
    --transition: all 0.3s ease;
}

/* Dark Mode (déclenché par la classe .dark-mode sur <body>, pas par le système) */
body.dark-mode {
    --text-primary: #e2e8f0;
    --text-secondary: #94a3b8;
    --bg-light: rgba(31,41,55,0.85);
    --bg-gradient: linear-gradient(135deg, #1f2937, #374151);
}

body.dark-mode .new-stat-card:not(.blue):not(.green):not(.yellow):not(.cyan) {
    background: #1e293b;
}

body.dark-mode .list-item {
    background: #1e293b;
    border-color: #334155;
}

body.dark-mode .info-card-header {
    background: linear-gradient(135deg, #1f2937, #111827);
    border-bottom-color: #334155;
}

body.dark-mode .progress-bar-custom {
    background: #334155;
}

body.dark-mode .empty-state {
    color: var(--text-secondary);
}

/* === Base Layout === */
body {
    background: var(--bg-gradient);
    font-family: 'Poppins', sans-serif;
    color: var(--text-primary);
    transition: background 0.3s ease, color 0.3s ease;
}

.container-fluid {
    padding: 2rem;
}

/* === Animations === */
.fade-in {
    animation: fadeIn 0.8s ease forwards;
    opacity: 0;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

[data-animate] {
    opacity: 0;
    transform: translateY(25px);
    transition: var(--transition);
}
[data-animate].visible {
    opacity: 1;
    transform: translateY(0);
}

/* === Welcome Header === */
.welcome-header {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    border-radius: 20px;
    padding: 2.5rem;
    margin-bottom: 2.5rem;
    box-shadow: var(--shadow-sm);
    position: sticky;
    top: 1rem;
    z-index: 10;
}
.welcome-header:hover {
    transform: translateY(-5px);
}
.welcome-title {
    font-size: 2.2rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 1rem;
}
.welcome-subtitle {
    margin-top: 0.6rem;
    opacity: 0.95;
    font-size: 1.1rem;
}
.welcome-subtitle span {
    font-weight: 700;
    text-decoration: underline;
}

/* === New Stat Cards (Matching Original Image) === */
.new-stat-card {
    display: inline-block;
    width: 200px;
    height: 100px;
    margin: 0 10px;
    background: #fff;
    border-radius: 15px;
    box-shadow: var(--shadow-sm);
    text-align: center;
    padding: 10px;
    transition: var(--transition);
}
.new-stat-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-md);
}
.new-stat-card.blue {
    background-color: #1e40af;
    color: white;
}
.new-stat-card.green {
    background-color: #16a34a;
    color: white;
}
.new-stat-card.yellow {
    background-color: #f59e0b;
    color: white;
}
.new-stat-card.cyan {
    background-color: #06b6d4;
    color: white;
}
.new-stat-card .label {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 5px;
}
.new-stat-card .value {
    font-size: 1.5rem;
    font-weight: 800;
}

/* === Stat Cards === */
.stat-card {
    border-radius: 16px;
    background: var(--bg-light);
    backdrop-filter: blur(8px);
    text-align: center;
    padding: 2rem 1.5rem;
    box-shadow: var(--shadow-sm);
    transition: var(--transition);
}
.stat-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow-md);
}
.stat-card-icon-top {
    font-size: 2.3rem;
    margin-bottom: 1rem;
    color: white;
    background: var(--card-color);
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    margin-inline: auto;
}
.stat-card-value {
    font-size: 2.5rem;
    font-weight: 900;
    color: var(--text-primary);
}
.stat-card-label {
    text-transform: uppercase;
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--text-secondary);
}
.stat-card-link {
    font-size: 0.85rem;
    font-weight: 600;
    background: var(--card-color);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    display: inline-block;
    margin-top: 1rem;
    text-decoration: none;
}
.stat-card-link:hover, .stat-card-link:focus {
    background: #1e40af;
    outline: 2px solid white;
}

/* === Mini Stats === */
.mini-stat {
    background: white;
    border-left: 5px solid var(--accent-color);
    border-radius: 16px;
    padding: 1.8rem;
    box-shadow: var(--shadow-sm);
}
.mini-stat:hover {
    transform: translateY(-5px);
}
.mini-stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--icon-bg), var(--icon-bg-end));
    color: var(--icon-color);
    font-size: 1.6rem;
}
.mini-stat-value {
    font-size: 2.2rem;
    font-weight: 900;
    margin: 0;
}
.mini-stat-label {
    color: var(--text-secondary);
    font-weight: 600;
    margin: 0;
}

/* === Info Cards === */
.info-card {
    background: var(--bg-light);
    border-radius: 18px;
    backdrop-filter: blur(8px);
    box-shadow: var(--shadow-sm);
}
.info-card:hover {
    transform: translateY(-5px);
}
.info-card-header {
    padding: 1.5rem;
    border-bottom: 2px solid #f1f5f9;
    background: linear-gradient(135deg, #f9fafb, #ffffff);
}
.info-card-title {
    font-weight: 800;
    font-size: 1.2rem;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 0.6rem;
}
.info-card-body {
    padding: 1.5rem;
    max-height: 420px;
    overflow-y: auto;
}

/* === List Items === */
.list-item {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 1rem;
    margin-bottom: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.list-item:hover {
    transform: translateX(6px);
    border-color: var(--primary-color);
}
.list-item-avatar {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--primary-color), #2563eb);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
}
.list-item-action {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: white;
    color: var(--primary-color);
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
}
.list-item-action:hover, .list-item-action:focus {
    background: var(--primary-color);
    color: white;
    outline: 2px solid white;
}

/* === Progress Bars === */
.progress-item {
    margin-bottom: 1.2rem;
}
.progress-header {
    display: flex;
    justify-content: space-between;
    font-weight: 700;
}
.progress-bar-custom {
    height: 12px;
    background: #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
}
.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--primary-color), #60a5fa);
    border-radius: 12px;
    position: relative;
    transition: width 1s ease-in-out;
}
.progress-fill::after {
    content: attr(data-percent) '%';
    position: absolute;
    right: 8px;
    top: -22px;
    font-size: 0.8rem;
    color: var(--primary-color);
    font-weight: 700;
}

/* === Empty State === */
.empty-state {
    text-align: center;
    padding: 2rem;
    color: var(--text-secondary);
}

/* === Responsive === */
@media (max-width: 768px) {
    .welcome-title { font-size: 1.6rem; }
    .container-fluid { padding: 1rem; }
    .new-stat-card { width: 150px; height: 80px; margin: 5px; }
    .new-stat-card .label { font-size: 0.9rem; }
    .new-stat-card .value { font-size: 1.2rem; }
    .stat-card { padding: 1.5rem; }
    .stat-card-value { font-size: 2rem; }
}
</style>
@endpush

@section('content')
<div class="container-fluid fade-in">
    <!-- Dark Mode Toggle -->
    <button class="btn btn-outline-secondary mb-3" id="darkModeToggle" aria-label="Toggle dark mode">
        <i class="fas fa-moon"></i><span id="darkModeLabel"> Mode Sombre</span>
    </button>

    <!-- Header -->
    <div class="welcome-header" role="banner">
        <h1 class="welcome-title"><i class="fas fa-tachometer-alt"></i> Tableau de bord Administrateur</h1>
        <p class="welcome-subtitle">Bienvenue 👋 <span>{{ auth()->user()->name }}</span></p>
    </div>

    <!-- New Stat Cards Section -->
    <div class="row g-4 mb-5" data-animate>
        <div class="col-12">
            <div class="d-flex justify-content-center">
                <div class="new-stat-card blue" role="region" aria-label="Total Salles">
                    <div class="label">Total Salles</div>
                    <div class="value">12</div>
                </div>
                <div class="new-stat-card green" role="region" aria-label="Salles Disponibles">
                    <div class="label">Disponibles</div>
                    <div class="value">10</div>
                </div>
                <div class="new-stat-card yellow" role="region" aria-label="Salles Occupées aujourd'hui">
                    <div class="label">Occupées aujourd'hui</div>
                    <div class="value">11</div>
                </div>
                <div class="new-stat-card cyan" role="region" aria-label="Capacité Totale">
                    <div class="label">Capacité totale</div>
                    <div class="value">475</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques principales -->
    <div class="row g-4 mb-5">
        @php
            $cards = [
                ['label'=>'Stagiaires','value'=>$data['total_stagiaires'],'color'=>'#3b82f6','icon'=>'fa-users','route'=>route('stagiaires.index'),'tooltip'=>'Nombre total de stagiaires inscrits'],
                ['label'=>'Filières','value'=>$data['total_filieres'],'color'=>'#6366f1','icon'=>'fa-layer-group','route'=>route('filieres.index'),'tooltip'=>'Nombre total de filières disponibles'],
                ['label'=>'Professeurs','value'=>$data['total_professeurs'],'color'=>'#06b6d4','icon'=>'fa-chalkboard-teacher','route'=>route('users.index',['role'=>'professeur']),'tooltip'=>'Nombre total de professeurs'],
                ['label'=>'Salles','value'=>$data['total_salles'],'color'=>'#10b981','icon'=>'fa-door-open','route'=>route('salles.index'),'tooltip'=>'Nombre total de salles disponibles'],
            ];
        @endphp

        @foreach($cards as $c)
            <div class="col-12 col-sm-6 col-lg-3" data-animate>
                <div class="stat-card" style="--card-color: {{ $c['color'] }}" data-tooltip="{{ $c['tooltip'] }}" role="region" aria-label="{{ $c['label'] }} statistics">
                    <div class="stat-card-icon-top"><i class="fas {{ $c['icon'] }}"></i></div>
                    <h2 class="stat-card-value counter" data-target="{{ $c['value'] }}" aria-live="polite">0</h2>
                    <p class="stat-card-label">{{ $c['label'] }}</p>
                    <a href="{{ $c['route'] }}" class="stat-card-link" aria-label="Voir les détails de {{ $c['label'] }}">Voir détails</a>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Mini Stats -->
    <div class="row g-4 mb-5">
        <div class="col-md-4" data-animate>
            <div class="mini-stat" style="--accent-color:#ef4444;--icon-bg:#fee2e2;--icon-bg-end:#fecaca;--icon-color:#ef4444;" role="region" aria-label="Absences aujourd'hui">
                <div class="d-flex align-items-center">
                    <div class="mini-stat-icon"><i class="fas fa-user-times"></i></div>
                    <div class="ms-3">
                        <h3 class="mini-stat-value">{{ $data['absences_aujourd_hui'] }}</h3>
                        <p class="mini-stat-label">Absences aujourd'hui</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4" data-animate>
            <div class="mini-stat" style="--accent-color:#3b82f6;--icon-bg:#dbeafe;--icon-bg-end:#bfdbfe;--icon-color:#3b82f6;" role="region" aria-label="Cours aujourd'hui">
                <div class="d-flex align-items-center">
                    <div class="mini-stat-icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="ms-3">
                        <h3 class="mini-stat-value">{{ $data['cours_aujourd_hui'] }}</h3>
                        <p class="mini-stat-label">Cours aujourd'hui</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4" data-animate>
            <div class="mini-stat" style="--accent-color:#10b981;--icon-bg:#d1fae5;--icon-bg-end:#a7f3d0;--icon-color:#10b981;" role="region" aria-label="Total Notes">
                <div class="d-flex align-items-center">
                    <div class="mini-stat-icon"><i class="fas fa-clipboard-list"></i></div>
                    <div class="ms-3">
                        <h3 class="mini-stat-value">{{ $data['total_notes'] }}</h3>
                        <p class="mini-stat-label">Total Notes</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dernières inscriptions & Filières -->
    <div class="row g-4">
        <div class="col-lg-6" data-animate>
            <div class="info-card" role="region" aria-label="Dernières inscriptions">
                <div class="info-card-header">
                    <h3 class="info-card-title"><i class="fas fa-user-plus" style="color:#3b82f6"></i> Dernières Inscriptions</h3>
                </div>
                <div class="info-card-body">
                    @forelse($data['recent_inscriptions'] as $stagiaire)
                        <div class="list-item">
                            <div class="d-flex align-items-center">
                                <div class="list-item-avatar"><i class="fas fa-user"></i></div>
                                <div class="ms-3">
                                    <p class="list-item-title mb-0">{{ $stagiaire->nom }} {{ $stagiaire->prenom }}</p>
                                    <p class="list-item-subtitle mb-0">{{ $stagiaire->filiere->nom }}</p>
                                </div>
                            </div>
                            <a href="{{ route('stagiaires.show', $stagiaire) }}" class="list-item-action" aria-label="Voir le profil de {{ $stagiaire->nom }}"><i class="fas fa-eye"></i></a>
                        </div>
                    @empty
                        <div class="empty-state"><i class="fas fa-inbox"></i><p>Aucune inscription récente</p></div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-6" data-animate>
            <div class="info-card" role="region" aria-label="Stagiaires par filière">
                <div class="info-card-header">
                    <h3 class="info-card-title"><i class="fas fa-chart-pie" style="color:#10b981"></i> Stagiaires par Filière</h3>
                </div>
                <div class="info-card-body">
                    @if($data['filieres_stats']->count() > 0)
                        @foreach($data['filieres_stats'] as $filiere)
                            @php $percent = round(($filiere->stagiaires_count / max($data['total_stagiaires'],1)) * 100); @endphp
                            <div class="progress-item">
                                <div class="progress-header">
                                    <span>{{ $filiere->nom }}</span>
                                    <span>{{ $filiere->stagiaires_count }}</span>
                                </div>
                                <div class="progress-bar-custom" role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-fill" style="width: {{ $percent }}%;" data-percent="{{ $percent }}"></div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="empty-state"><i class="fas fa-chart-bar"></i><p>Aucune donnée disponible</p></div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JS: Counter + Scroll Animation + Dark Mode -->
<script>
    // Optimized Counter Animation
    document.querySelectorAll('.counter').forEach(counter => {
        const target = +counter.getAttribute('data-target');
        const duration = 1000; // Animation duration in ms
        const start = performance.now();

        function updateCounter(time) {
            const elapsed = time - start;
            const progress = Math.min(elapsed / duration, 1);
            counter.textContent = Math.floor(progress * target);
            if (progress < 1) requestAnimationFrame(updateCounter);
        }

        requestAnimationFrame(updateCounter);
    });

    // Scroll Animation
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('[data-animate]').forEach(el => observer.observe(el));

    // Dark Mode Toggle
    const toggle = document.getElementById('darkModeToggle');
    const toggleIcon = toggle.querySelector('i');
    const toggleLabel = document.getElementById('darkModeLabel');

    // Restaurer la préférence enregistrée
    if (localStorage.getItem('darkMode') === 'true') {
        document.body.classList.add('dark-mode');
        toggleIcon.classList.replace('fa-moon', 'fa-sun');
        toggleLabel.textContent = ' Mode Clair';
    }

    toggle.addEventListener('click', () => {
        const isDark = document.body.classList.toggle('dark-mode');
        toggleIcon.classList.toggle('fa-moon');
        toggleIcon.classList.toggle('fa-sun');
        toggleLabel.textContent = isDark ? ' Mode Clair' : ' Mode Sombre';
        localStorage.setItem('darkMode', isDark);
    });
</script>
@endsection