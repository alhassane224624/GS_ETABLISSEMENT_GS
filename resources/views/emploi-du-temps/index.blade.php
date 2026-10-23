@extends('layouts.app')

@section('title', 'Emploi du temps')
@section('page-title', 'Emploi du temps hebdomadaire')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
        <label class="text-muted text-nowrap">Année scolaire</label>
        <select name="annee_id" class="form-select w-auto" onchange="this.form.classe_id.value=''; this.form.submit()">
            @foreach ($annees as $a)
                <option value="{{ $a->id }}" @selected(optional($annee)->id === $a->id)>{{ $a->nom }}{{ $a->is_active ? ' (active)' : '' }}</option>
            @endforeach
        </select>
        <label class="text-muted text-nowrap ms-2">Classe</label>
        <select name="classe_id" class="form-select w-auto" onchange="this.form.submit()">
            @foreach ($classes as $c)
                <option value="{{ $c->id }}" @selected(optional($classe)->id === $c->id)>{{ $c->nom }} — {{ $c->filiere->nom ?? '' }}</option>
            @endforeach
        </select>
    </form>
    @if ($classe)
        <span class="badge bg-primary fs-6"><i class="fas fa-clock me-1"></i>{{ rtrim(rtrim(number_format($volume, 2, ',', ''), '0'), ',') }} h / semaine</span>
    @endif
</div>

<div class="alert alert-light border small">
    <strong>Fonctionnement :</strong>
    1) saisissez les <strong>créneaux</strong> récurrents de chaque classe (un créneau = un cours qui revient chaque semaine) ;
    2) <strong>générez les séances</strong> pour la période : elles apparaissent alors dans le planning des professeurs et des stagiaires.
    Pour changer un horaire, supprimez le créneau puis recréez-le.
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if (session('conflits_generation'))
    <div class="alert alert-warning">
        <strong><i class="fas fa-exclamation-triangle me-1"></i>Séances non créées (conflit) :</strong>
        <ul class="mb-0 small">
            @foreach (session('conflits_generation') as $conflit)
                <li>{{ $conflit }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if (!$annee)
    <div class="alert alert-info">Aucune année scolaire n'existe encore. Créez-en une dans <a href="{{ route('annees-scolaires.index') }}">Années scolaires</a>.</div>
@elseif (!$classe)
    <div class="alert alert-info">Aucune classe pour cette année scolaire. Créez-en une dans <a href="{{ route('classes.index') }}">Classes</a>.</div>
@else
    {{-- Grille de la semaine --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold">
            <i class="fas fa-calendar-week text-primary me-2"></i>{{ $classe->nom }}
        </div>
        <div class="table-responsive">
            <table class="table table-bordered align-top mb-0" style="min-width: 900px;">
                <thead class="table-light">
                    <tr>
                        @foreach (\App\Models\Creneau::JOURS as $libelle)
                            <th class="text-center" style="width: 16.66%;">{{ $libelle }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        @foreach (\App\Models\Creneau::JOURS as $jour => $libelle)
                            <td class="p-2">
                                @forelse ($creneaux->get($jour, collect()) as $c)
                                    <div class="border rounded p-2 mb-2 {{ $c->is_active ? 'border-primary' : 'bg-light text-muted' }}">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <strong class="small">{{ $c->horaire }}</strong>
                                            <span class="badge bg-secondary">{{ \App\Models\Creneau::TYPES[$c->type_cours] ?? $c->type_cours }}</span>
                                        </div>
                                        <div class="fw-semibold">{{ $c->matiere->nom ?? '—' }}</div>
                                        <div class="small"><i class="fas fa-chalkboard-teacher me-1"></i>{{ $c->professeur->name ?? '—' }}</div>
                                        <div class="small"><i class="fas fa-door-open me-1"></i>{{ $c->salle->nom ?? '—' }}</div>
                                        @unless ($c->is_active)
                                            <span class="badge bg-warning text-dark mt-1">Désactivé</span>
                                        @endunless
                                        <div class="d-flex gap-1 mt-2">
                                            <form method="POST" action="{{ route('emploi-du-temps.update', $c) }}"
                                                  onsubmit="return {{ $c->is_active ? "confirm('Désactiver ce créneau ? Les séances à venir seront supprimées.')" : 'true' }}">
                                                @csrf @method('PATCH')
                                                <button class="btn btn-sm {{ $c->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}" title="{{ $c->is_active ? 'Désactiver' : 'Réactiver' }}">
                                                    <i class="fas {{ $c->is_active ? 'fa-pause' : 'fa-play' }}"></i>
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('emploi-du-temps.destroy', $c) }}"
                                                  onsubmit="return confirm('Supprimer ce créneau ? Les séances à venir seront supprimées, les séances passées conservées.')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-muted small text-center py-3">—</div>
                                @endforelse
                            </td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-4">
        {{-- Ajout d'un créneau --}}
        <div class="col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold"><i class="fas fa-plus-circle text-success me-2"></i>Ajouter un créneau</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('emploi-du-temps.store') }}">
                        @csrf
                        <input type="hidden" name="annee_scolaire_id" value="{{ $annee->id }}">
                        <input type="hidden" name="classe_id" value="{{ $classe->id }}">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Matière</label>
                                <select name="matiere_id" class="form-select" required>
                                    <option value="">Choisir…</option>
                                    @foreach ($matieres as $m)
                                        <option value="{{ $m->id }}" @selected(old('matiere_id') == $m->id)>{{ $m->nom }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Professeur</label>
                                <select name="professeur_id" class="form-select" required>
                                    <option value="">Choisir…</option>
                                    @foreach ($professeurs as $p)
                                        <option value="{{ $p->id }}" @selected(old('professeur_id') == $p->id)>{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Salle</label>
                                <select name="salle_id" class="form-select" required>
                                    <option value="">Choisir…</option>
                                    @foreach ($salles as $s)
                                        <option value="{{ $s->id }}" @selected(old('salle_id') == $s->id)>{{ $s->nom }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Type</label>
                                <select name="type_cours" class="form-select" required>
                                    @foreach (\App\Models\Creneau::TYPES as $valeur => $libelle)
                                        <option value="{{ $valeur }}" @selected(old('type_cours', 'cours') === $valeur)>{{ $libelle }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Jour</label>
                                <select name="jour" class="form-select" required>
                                    @foreach (\App\Models\Creneau::JOURS as $num => $libelle)
                                        <option value="{{ $num }}" @selected(old('jour') == $num)>{{ $libelle }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Début</label>
                                <input type="time" name="heure_debut" value="{{ old('heure_debut', '08:30') }}" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Fin</label>
                                <input type="time" name="heure_fin" value="{{ old('heure_fin', '10:30') }}" class="form-control" required>
                            </div>
                        </div>
                        <button class="btn btn-success mt-3"><i class="fas fa-save me-1"></i>Ajouter le créneau</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Génération des séances --}}
        <div class="col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold"><i class="fas fa-magic text-primary me-2"></i>Générer les séances</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('emploi-du-temps.generer') }}"
                          onsubmit="return confirm('Générer les séances pour cette période ? Les séances déjà existantes ne seront pas dupliquées.')">
                        @csrf
                        <input type="hidden" name="annee_scolaire_id" value="{{ $annee->id }}">
                        <input type="hidden" name="classe_id" value="{{ $classe->id }}">
                        <div class="mb-3">
                            <label class="form-label">Classes concernées</label>
                            <select name="portee" class="form-select">
                                <option value="classe" @selected(old('portee', 'classe') === 'classe')>Uniquement {{ $classe->nom }}</option>
                                <option value="toutes" @selected(old('portee') === 'toutes')>Toutes les classes de {{ $annee->nom }}</option>
                            </select>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label">Du</label>
                                <input type="date" name="date_debut" value="{{ old('date_debut', $genererDebut) }}" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Au</label>
                                <input type="date" name="date_fin" value="{{ old('date_fin', $genererFin) }}" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jours exclus <span class="text-muted small">(fériés, vacances)</span></label>
                            <textarea name="jours_exclus" rows="3" class="form-control" placeholder="Ex. : 18/11/2026, 01/01/2027">{{ old('jours_exclus') }}</textarea>
                            <div class="form-text">Dates séparées par des virgules ou des retours à la ligne (jj/mm/aaaa). 200 jours maximum par génération.</div>
                        </div>
                        <button class="btn btn-primary"><i class="fas fa-cogs me-1"></i>Générer</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection
