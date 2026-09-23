@extends('layouts.app')

@section('title', 'Liste des Stagiaires')
@section('page-title', 'Liste des Stagiaires')

@section('content')

<style>
    /* =========================================================
       GLOBAL
    ========================================================= */

    body {
        background: #f5f7fb;
        font-family: 'Poppins', sans-serif;
        color: #1e293b;
    }

    .stagiaires-page {
        animation: pageFade .45s ease;
    }

    @keyframes pageFade {
        from {
            opacity: 0;
            transform: translateY(8px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }


    /* =========================================================
       HEADER
    ========================================================= */

    .page-header {
        background: #ffffff;
        border-radius: 18px;
        padding: 22px 25px;
        border: 1px solid #e8edf5;
        box-shadow: 0 5px 20px rgba(15, 23, 42, .04);
    }

    .page-title {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .page-title-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #2563eb, #4f46e5);
        color: #fff;
        font-size: 20px;
        box-shadow: 0 7px 18px rgba(37, 99, 235, .22);
    }

    .page-title h1 {
        font-size: 1.35rem;
        font-weight: 800;
        margin: 0;
        color: #172033;
    }

    .page-title p {
        margin: 3px 0 0;
        color: #64748b;
        font-size: .84rem;
    }


    /* =========================================================
       BUTTONS
    ========================================================= */

    .btn-modern {
        border: none;
        border-radius: 11px;
        padding: 10px 16px;
        font-size: .88rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        transition: all .25s ease;
        text-decoration: none;
    }

    .btn-modern:hover {
        transform: translateY(-2px);
        text-decoration: none;
    }

    .btn-excel {
        background: #16a34a;
        color: white;
        box-shadow: 0 5px 14px rgba(22, 163, 74, .18);
    }

    .btn-excel:hover {
        background: #15803d;
        color: white;
    }

    .btn-add {
        background: linear-gradient(135deg, #2563eb, #4f46e5);
        color: white;
        box-shadow: 0 5px 14px rgba(37, 99, 235, .20);
    }

    .btn-add:hover {
        color: white;
        box-shadow: 0 8px 20px rgba(37, 99, 235, .28);
    }


    /* =========================================================
       STATISTICS
    ========================================================= */

    .stat-card {
        position: relative;
        overflow: hidden;
        background: #fff;
        border: 1px solid #e8edf5;
        border-radius: 17px;
        padding: 19px;
        box-shadow: 0 5px 20px rgba(15, 23, 42, .04);
        transition: all .25s ease;
        height: 100%;
    }

    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 25px rgba(15, 23, 42, .08);
    }

    .stat-card::after {
        content: "";
        position: absolute;
        right: -25px;
        bottom: -35px;
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: rgba(37, 99, 235, .04);
    }

    .stat-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .stat-icon {
        width: 46px;
        height: 46px;
        border-radius: 13px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    .icon-blue {
        background: #e8efff;
        color: #2563eb;
    }

    .icon-green {
        background: #e8f8ef;
        color: #16a34a;
    }

    .icon-orange {
        background: #fff4df;
        color: #d97706;
    }

    .icon-purple {
        background: #f1eaff;
        color: #7c3aed;
    }

    .stat-label {
        margin-top: 15px;
        color: #64748b;
        font-size: .82rem;
        font-weight: 600;
    }

    .stat-value {
        margin-top: 3px;
        font-size: 1.65rem;
        line-height: 1;
        font-weight: 800;
        color: #172033;
    }


    /* =========================================================
       FILTER CARD
    ========================================================= */

    .filter-card {
        background: #fff;
        border: 1px solid #e8edf5;
        border-radius: 18px;
        padding: 22px;
        box-shadow: 0 5px 20px rgba(15, 23, 42, .04);
    }

    .filter-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 18px;
    }

    .filter-header-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: #eef2ff;
        color: #4f46e5;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .filter-title {
        font-weight: 800;
        color: #172033;
        margin: 0;
        font-size: .96rem;
    }

    .filter-subtitle {
        color: #94a3b8;
        font-size: .76rem;
        margin: 2px 0 0;
    }

    .filter-label {
        font-size: .76rem;
        font-weight: 700;
        color: #475569;
        margin-bottom: 7px;
        display: block;
    }

    .form-modern {
        border: 1px solid #dbe2ea;
        border-radius: 10px;
        min-height: 43px;
        font-size: .86rem;
        background: #fff;
        transition: all .2s ease;
    }

    .form-modern:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, .10);
    }

    .search-wrapper {
        position: relative;
    }

    .search-wrapper i {
        position: absolute;
        left: 13px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
    }

    .search-wrapper input {
        padding-left: 38px;
    }

    .filter-actions {
        margin-top: 18px;
        display: flex;
        gap: 9px;
    }

    .btn-filter {
        background: #2563eb;
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: 9px 17px;
        font-weight: 700;
        font-size: .84rem;
    }

    .btn-filter:hover {
        background: #1d4ed8;
        color: white;
    }

    .btn-reset {
        background: #f1f5f9;
        color: #475569;
        border: none;
        border-radius: 10px;
        padding: 9px 17px;
        font-weight: 700;
        font-size: .84rem;
        text-decoration: none;
    }

    .btn-reset:hover {
        background: #e2e8f0;
        color: #334155;
    }


    /* =========================================================
       TABLE
    ========================================================= */

    .table-card {
        background: #fff;
        border: 1px solid #e8edf5;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(15, 23, 42, .04);
    }

    .table-header {
        padding: 18px 22px;
        border-bottom: 1px solid #edf1f6;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .table-title {
        font-weight: 800;
        color: #172033;
        font-size: .96rem;
        margin: 0;
    }

    .table-count {
        font-size: .76rem;
        color: #64748b;
        background: #f1f5f9;
        padding: 5px 10px;
        border-radius: 20px;
        font-weight: 700;
    }

    .modern-table {
        margin: 0;
        min-width: 850px;
    }

    .modern-table thead th {
        background: #f8fafc;
        color: #64748b;
        font-size: .70rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .04em;
        padding: 14px 18px;
        border-bottom: 1px solid #e8edf5;
        white-space: nowrap;
    }

    .modern-table tbody td {
        padding: 14px 18px;
        border-bottom: 1px solid #f0f2f5;
        vertical-align: middle;
        font-size: .83rem;
        color: #475569;
    }

    .modern-table tbody tr {
        transition: background .2s ease;
    }

    .modern-table tbody tr:hover {
        background: #f8faff;
    }

    .modern-table tbody tr:last-child td {
        border-bottom: none;
    }


    /* =========================================================
       STUDENT
    ========================================================= */

    .student-info {
        display: flex;
        align-items: center;
        gap: 11px;
    }

    .student-photo {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        object-fit: cover;
        border: 2px solid #e8edf5;
    }

    .student-name {
        font-weight: 800;
        color: #1e293b;
        font-size: .85rem;
    }

    .student-firstname {
        color: #64748b;
        font-size: .78rem;
        margin-top: 2px;
    }

    .matricule {
        font-weight: 700;
        color: #2563eb;
        background: #eff6ff;
        border-radius: 7px;
        padding: 5px 8px;
        font-size: .73rem;
    }


    /* =========================================================
       BADGES
    ========================================================= */

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 6px 10px;
        border-radius: 30px;
        font-size: .70rem;
        font-weight: 800;
    }

    .status-badge::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
    }

    .status-actif {
        color: #15803d;
        background: #dcfce7;
    }

    .status-suspendu {
        color: #b45309;
        background: #fef3c7;
    }

    .status-diplome {
        color: #2563eb;
        background: #dbeafe;
    }

    .status-abandonne {
        color: #dc2626;
        background: #fee2e2;
    }

    .status-transfere {
        color: #7c3aed;
        background: #ede9fe;
    }


    /* =========================================================
       ACTIONS
    ========================================================= */

    .action-group {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .action-btn {
        width: 33px;
        height: 33px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: none;
        transition: all .2s ease;
        text-decoration: none;
        font-size: .78rem;
    }

    .action-view {
        background: #eff6ff;
        color: #2563eb;
    }

    .action-edit {
        background: #fff7ed;
        color: #ea580c;
    }

    .action-delete {
        background: #fef2f2;
        color: #dc2626;
    }

    .action-btn:hover {
        transform: translateY(-2px);
        filter: brightness(.95);
    }


    /* =========================================================
       EMPTY STATE
    ========================================================= */

    .empty-state {
        padding: 55px 20px !important;
        text-align: center;
    }

    .empty-icon {
        width: 65px;
        height: 65px;
        margin: auto;
        border-radius: 18px;
        background: #f1f5f9;
        color: #94a3b8;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 25px;
    }

    .empty-state h5 {
        margin-top: 15px;
        font-weight: 800;
        color: #334155;
    }

    .empty-state p {
        color: #94a3b8;
        font-size: .84rem;
    }


    /* =========================================================
       PAGINATION
    ========================================================= */

    .pagination-wrapper {
        padding: 15px 20px;
        background: #fafbfc;
        border-top: 1px solid #edf1f6;
    }


    /* =========================================================
       ALERTS
    ========================================================= */

    .modern-alert {
        border: none;
        border-radius: 13px;
        padding: 13px 17px;
        font-size: .85rem;
        box-shadow: 0 4px 12px rgba(15, 23, 42, .04);
    }


    /* =========================================================
       RESPONSIVE
    ========================================================= */

    @media (max-width: 768px) {

        .page-header {
            padding: 18px;
        }

        .page-header .header-actions {
            width: 100%;
        }

        .page-header .header-actions a {
            flex: 1;
            justify-content: center;
        }

        .stat-value {
            font-size: 1.4rem;
        }

        .filter-card {
            padding: 17px;
        }

        .table-header {
            padding: 15px;
        }
    }
</style>


<div class="stagiaires-page">

    {{-- =====================================================
         HEADER
    ====================================================== --}}

    <div class="page-header mb-4">

        <div class="d-flex flex-column flex-lg-row
                    align-items-lg-center justify-content-between gap-3">

            <div class="page-title">

                <div class="page-title-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>

                <div>
                    <h1>Gestion des stagiaires</h1>
                    <p>Consultez, recherchez et gérez les stagiaires de l'établissement.</p>
                </div>

            </div>


            <div class="header-actions d-flex gap-2">

                <a href="{{ route('stagiaires.export', request()->query()) }}"
                   class="btn-modern btn-excel">

                    <i class="fas fa-file-excel"></i>

                    Exporter Excel

                </a>


                <a href="{{ route('stagiaires.create') }}"
                   class="btn-modern btn-add">

                    <i class="fas fa-plus"></i>

                    Nouveau stagiaire

                </a>

            </div>

        </div>

    </div>


    {{-- =====================================================
         ALERTES
    ====================================================== --}}

    @if(session('success'))

        <div class="alert alert-success modern-alert
                    d-flex align-items-center mb-4">

            <i class="fas fa-check-circle me-2"></i>

            {{ session('success') }}

        </div>

    @endif


    @if(session('error'))

        <div class="alert alert-danger modern-alert
                    d-flex align-items-center mb-4">

            <i class="fas fa-exclamation-circle me-2"></i>

            {{ session('error') }}

        </div>

    @endif


   <!-- === STATISTIQUES === -->
<div class="row g-3 mt-3">
    <div class="col-md-3">
        <a href="{{ route('stagiaires.index') }}" class="stat-card-link-wrap">
            <div class="stat-card">
                <div class="stat-icon bg-primary"><i class="fas fa-users"></i></div>
                <div class="stat-text">
                    <div class="stat-label">Total</div>
                    <div class="stat-value">{{ $stagiaires->total() }}</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('stagiaires.index', ['statut' => 'actif']) }}" class="stat-card-link-wrap">
            <div class="stat-card">
                <div class="stat-icon bg-success"><i class="fas fa-check-circle"></i></div>
                <div class="stat-text">
                    <div class="stat-label">Actifs</div>
                    <div class="stat-value">{{ \App\Models\Stagiaire::where('statut', 'actif')->count() }}</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('stagiaires.index', ['statut' => 'suspendu']) }}" class="stat-card-link-wrap">
            <div class="stat-card">
                <div class="stat-icon bg-warning"><i class="fas fa-pause-circle"></i></div>
                <div class="stat-text">
                    <div class="stat-label">Suspendus</div>
                    <div class="stat-value">{{ \App\Models\Stagiaire::where('statut', 'suspendu')->count() }}</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('stagiaires.index', ['statut' => 'diplome']) }}" class="stat-card-link-wrap">
            <div class="stat-card">
                <div class="stat-icon bg-purple"><i class="fas fa-graduation-cap"></i></div>
                <div class="stat-text">
                    <div class="stat-label">Diplômés</div>
                    <div class="stat-value">{{ \App\Models\Stagiaire::where('statut', 'diplome')->count() }}</div>
                </div>
            </div>
        </a>
    </div>
</div>

    {{-- =====================================================
         FILTRES
    ====================================================== --}}

    <div class="filter-card mb-4">

        <div class="filter-header">

            <div class="filter-header-icon">
                <i class="fas fa-sliders-h"></i>
            </div>

            <div>
                <h5 class="filter-title">
                    Rechercher et filtrer
                </h5>

                <p class="filter-subtitle">
                    Affinez la liste des stagiaires
                </p>
            </div>

        </div>


        <form method="GET"
              action="{{ route('stagiaires.index') }}">

            <div class="row g-3">

                {{-- RECHERCHE --}}

                <div class="col-12 col-lg-3">

                    <label class="filter-label">
                        Recherche
                    </label>

                    <div class="search-wrapper">

                        <i class="fas fa-search"></i>

                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Nom, prénom, matricule..."
                            class="form-control form-modern"
                        >

                    </div>

                </div>


                {{-- FILIERE --}}

                <div class="col-12 col-sm-6 col-lg-3">

                    <label class="filter-label">
                        Filière
                    </label>

                    <select name="filiere_id"
                            class="form-select form-modern">

                        <option value="">
                            Toutes les filières
                        </option>

                        @foreach($filieres as $filiere)

                            <option
                                value="{{ $filiere->id }}"
                                {{ request('filiere_id') == $filiere->id ? 'selected' : '' }}
                            >
                                {{ $filiere->nom }}
                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- CLASSE --}}

                <div class="col-12 col-sm-6 col-lg-3">

                    <label class="filter-label">
                        Classe
                    </label>

                    <select name="classe_id"
                            class="form-select form-modern">

                        <option value="">
                            Toutes les classes
                        </option>

                        @foreach($classes as $classe)

                            <option
                                value="{{ $classe->id }}"
                                {{ request('classe_id') == $classe->id ? 'selected' : '' }}
                            >
                                {{ $classe->nom }}
                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- STATUT --}}

                <div class="col-12 col-sm-6 col-lg-3">

                    <label class="filter-label">
                        Statut
                    </label>

                    <select name="statut"
                            class="form-select form-modern">

                        <option value="">
                            Tous les statuts
                        </option>

                        <option value="actif"
                            {{ request('statut') == 'actif' ? 'selected' : '' }}>
                            Actif
                        </option>

                        <option value="suspendu"
                            {{ request('statut') == 'suspendu' ? 'selected' : '' }}>
                            Suspendu
                        </option>

                        <option value="diplome"
                            {{ request('statut') == 'diplome' ? 'selected' : '' }}>
                            Diplômé
                        </option>

                        <option value="abandonne"
                            {{ request('statut') == 'abandonne' ? 'selected' : '' }}>
                            Abandonné
                        </option>

                        <option value="transfere"
                            {{ request('statut') == 'transfere' ? 'selected' : '' }}>
                            Transféré
                        </option>

                    </select>

                </div>

            </div>


            <div class="filter-actions">

                <button type="submit"
                        class="btn-filter">

                    <i class="fas fa-search me-1"></i>

                    Appliquer les filtres

                </button>


                <a href="{{ route('stagiaires.index') }}"
                   class="btn-reset">

                    <i class="fas fa-rotate-left me-1"></i>

                    Réinitialiser

                </a>

            </div>

        </form>

    </div>


    {{-- =====================================================
         TABLEAU
    ====================================================== --}}

    <div class="table-card">

        <div class="table-header">

            <div>

                <h5 class="table-title">
                    Liste des stagiaires
                </h5>

            </div>

            <span class="table-count">

                {{ $stagiaires->total() }}

                stagiaire(s)

            </span>

        </div>


        <div class="table-responsive">

            <table class="table modern-table align-middle">

                <thead>

                    <tr>

                        <th>Stagiaire</th>

                        <th>Matricule</th>

                        <th>Filière</th>

                        <th>Classe</th>

                        <th>Statut</th>

                        <th class="text-end">
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse($stagiaires as $stagiaire)

                        <tr>

                            {{-- STAGIAIRE --}}

                            <td>

                                <div class="student-info">

                                    <img
                                        src="{{ $stagiaire->photo_url }}"
                                        alt="{{ $stagiaire->nom_complet }}"
                                        class="student-photo"
                                    >

                                    <div>

                                        <div class="student-name">
                                            {{ $stagiaire->nom }}
                                        </div>

                                        <div class="student-firstname">
                                            {{ $stagiaire->prenom }}
                                        </div>

                                    </div>

                                </div>

                            </td>


                            {{-- MATRICULE --}}

                            <td>

                                <span class="matricule">

                                    {{ $stagiaire->matricule }}

                                </span>

                            </td>


                            {{-- FILIERE --}}

                            <td>

                                {{ $stagiaire->filiere->nom ?? '—' }}

                            </td>


                            {{-- CLASSE --}}

                            <td>

                                {{ $stagiaire->classe->nom ?? '—' }}

                            </td>


                            {{-- STATUT --}}

                            <td>

                                @php

                                    $statusClass = [

                                        'actif' =>
                                            'status-actif',

                                        'suspendu' =>
                                            'status-suspendu',

                                        'diplome' =>
                                            'status-diplome',

                                        'abandonne' =>
                                            'status-abandonne',

                                        'transfere' =>
                                            'status-transfere',

                                    ];

                                @endphp


                                <span class="status-badge
                                    {{ $statusClass[$stagiaire->statut] ?? '' }}">

                                    {{ $stagiaire->statut_libelle }}

                                </span>

                            </td>


                            {{-- ACTIONS --}}

                            <td>

                                <div class="action-group justify-content-end">

                                    <a
                                        href="{{ route('stagiaires.show', $stagiaire) }}"
                                        class="action-btn action-view"
                                        title="Voir"
                                    >

                                        <i class="fas fa-eye"></i>

                                    </a>


                                    <a
                                        href="{{ route('stagiaires.edit', $stagiaire) }}"
                                        class="action-btn action-edit"
                                        title="Modifier"
                                    >

                                        <i class="fas fa-pen"></i>

                                    </a>


                                    <form
                                        action="{{ route('stagiaires.destroy', $stagiaire) }}"
                                        method="POST"
                                        class="d-inline"
                                        onsubmit="return confirm('Supprimer ce stagiaire ?');"
                                    >

                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="action-btn action-delete"
                                            title="Supprimer"
                                        >

                                            <i class="fas fa-trash"></i>

                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="6"
                                class="empty-state">

                                <div class="empty-icon">

                                    <i class="fas fa-user-slash"></i>

                                </div>

                                <h5>
                                    Aucun stagiaire trouvé
                                </h5>

                                <p>
                                    Aucun résultat ne correspond aux critères sélectionnés.
                                </p>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- PAGINATION --}}

        @if($stagiaires->hasPages())

            <div class="pagination-wrapper">

                {{ $stagiaires->withQueryString()->links() }}

            </div>

        @endif

    </div>

</div>


<script src="https://kit.fontawesome.com/a2e0e9c6a4.js"
        crossorigin="anonymous"></script>

@endsection    