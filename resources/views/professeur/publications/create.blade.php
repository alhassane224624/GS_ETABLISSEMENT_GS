@extends('layouts.app-professeur')

@section('title', 'Nouvelle publication')
@section('page-title', 'Nouvelle publication')

@section('content')
<div class="container-fluid">
    <div class="mb-3">
        <a href="{{ route('professeur.publications.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Mes publications</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger shadow-sm">
            <div class="fw-semibold mb-1"><i class="fas fa-exclamation-triangle me-2"></i>La publication n'a pas été faite :</div>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($classes->isEmpty())
        <div class="alert alert-info">Aucune classe ne vous est affectée pour l'année en cours. Contactez l'administration pour vos affectations (filières et matières).</div>
    @else
    <form method="POST" action="{{ route('professeur.publications.store') }}" enctype="multipart/form-data" id="form_publication">
        @csrf

        {{-- Type --}}
        <div class="row g-3 mb-4">
            @php $typeChoisi = old('type', $type); @endphp
            @foreach ([
                'devoir' => ['Devoir', 'Un travail à faire pour une date, avec dépôt en ligne possible.'],
                'document' => ['Document', 'Support de cours, énoncé, fiche d\'exercices…'],
                'resultats' => ['Résultats', 'Rendre visibles les notes d\'un contrôle déjà saisies, avec un corrigé si besoin.'],
            ] as $valeur => [$libelle, $aide])
                <div class="col-md-4">
                    <input type="radio" class="btn-check" name="type" id="type_{{ $valeur }}" value="{{ $valeur }}" @checked($typeChoisi === $valeur)>
                    <label class="btn btn-outline-{{ \App\Models\Publication::COULEURS[$valeur] }} w-100 text-start p-3 h-100" for="type_{{ $valeur }}">
                        <i class="fas {{ \App\Models\Publication::ICONES[$valeur] }} me-2"></i><strong>{{ $libelle }}</strong>
                        <div class="small mt-1 opacity-75">{{ $aide }}</div>
                    </label>
                </div>
            @endforeach
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Classe <span class="text-danger">*</span></label>
                        <select name="classe_id" id="classe_id" class="form-select" required>
                            <option value="">Choisir…</option>
                            @foreach ($classes as $c)
                                <option value="{{ $c->id }}" data-matieres="{{ $c->matieresProf->map(fn ($m) => ['id' => $m->id, 'nom' => $m->nom])->toJson() }}"
                                        @selected(old('classe_id', request('classe_id')) == $c->id)>{{ $c->nom }} — {{ $c->filiere->nom ?? '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Matière <span class="text-danger">*</span></label>
                        <select name="matiere_id" id="matiere_id" class="form-select" required data-initial="{{ old('matiere_id', request('matiere_id')) }}">
                            <option value="">Choisissez d'abord la classe</option>
                        </select>
                    </div>

                    <div class="col-12" id="bloc_resultats">
                        <div class="alert mb-0" id="info_resultats"></div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Titre <span class="text-danger">*</span></label>
                        <input type="text" name="titre" value="{{ old('titre') }}" required maxlength="255" class="form-control" placeholder="Ex. : Exercices chapitre 3, Contrôle n°2…">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Consignes / message</label>
                        <textarea name="contenu" rows="4" maxlength="5000" class="form-control" placeholder="Consignes du devoir, remarques sur le contrôle…">{{ old('contenu') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" id="label_fichier">Fichier joint</label>
                        <input type="file" name="fichier" class="form-control">
                        <div class="form-text">PDF, Word, Excel, PowerPoint, image ou ZIP — 10 Mo maximum.</div>
                    </div>

                    <div class="col-md-6" id="bloc_devoir">
                        <label class="form-label">À rendre pour le <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="date_limite" id="date_limite" value="{{ old('date_limite', now()->addWeek()->setTime(23, 59)->format('Y-m-d\TH:i')) }}" class="form-control">
                        <div class="form-check form-switch mt-2">
                            <input type="hidden" name="accepte_rendu" value="0">
                            <input type="checkbox" class="form-check-input" name="accepte_rendu" id="accepte_rendu" value="1" @checked(old('accepte_rendu', true))>
                            <label class="form-check-label" for="accepte_rendu">Les stagiaires déposent leur copie en ligne</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('professeur.publications.index') }}" class="btn btn-outline-secondary">Annuler</a>
            <button type="submit" class="btn btn-primary" id="btn_publier"><i class="fas fa-paper-plane me-1"></i>Publier</button>
        </div>
    </form>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('form_publication');
    if (!form) return;

    const classe = document.getElementById('classe_id');
    const matiere = document.getElementById('matiere_id');
    const blocDevoir = document.getElementById('bloc_devoir');
    const blocResultats = document.getElementById('bloc_resultats');
    const info = document.getElementById('info_resultats');
    const dateLimite = document.getElementById('date_limite');
    // Notes non publiées : « classe-matière » => nombre
    const enAttente = @json($enAttente->mapWithKeys(fn ($g) => [$g->classe_id . '-' . $g->matiere_id => (int) $g->total]));

    const type = () => form.querySelector('input[name="type"]:checked')?.value;

    function remplirMatieres() {
        const choix = matiere.value || matiere.dataset.initial;
        const opt = classe.selectedOptions[0];
        const liste = opt && opt.dataset.matieres ? JSON.parse(opt.dataset.matieres) : [];
        matiere.innerHTML = '<option value="">' + (liste.length ? 'Choisir…' : 'Choisissez d\'abord la classe') + '</option>';
        liste.forEach(m => matiere.add(new Option(m.nom, m.id, false, String(m.id) === String(choix))));
        matiere.dataset.initial = '';
    }

    function maj() {
        const t = type();
        blocDevoir.style.display = t === 'devoir' ? '' : 'none';
        dateLimite.required = t === 'devoir';
        blocResultats.style.display = t === 'resultats' ? '' : 'none';
        document.getElementById('label_fichier').textContent = t === 'resultats' ? 'Corrigé (facultatif)' : 'Fichier joint';

        if (t === 'resultats') {
            const n = enAttente[classe.value + '-' + matiere.value] || 0;
            info.className = 'alert mb-0 ' + (n ? 'alert-success' : 'alert-secondary');
            info.innerHTML = !classe.value || !matiere.value
                ? 'Choisissez la classe et la matière : les notes que vous y avez saisies et pas encore publiées deviendront visibles.'
                : (n ? '<i class="fas fa-check-circle me-1"></i><strong>' + n + ' note(s)</strong> seront rendues visibles aux stagiaires.'
                     : 'Aucune note en attente pour cette classe et cette matière. Saisissez d\'abord les notes (menu Mes stagiaires).');
        }
    }

    classe.addEventListener('change', () => { remplirMatieres(); maj(); });
    matiere.addEventListener('change', maj);
    form.querySelectorAll('input[name="type"]').forEach(r => r.addEventListener('change', maj));
    remplirMatieres();
    maj();

    form.addEventListener('submit', () => {
        const btn = document.getElementById('btn_publier');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Publication…';
    });
})();
</script>
@endpush
