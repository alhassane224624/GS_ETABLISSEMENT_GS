@extends('layouts.app')

@section('title', 'Justificatifs d\'absence')
@section('page-title', 'Justificatifs d\'absence')

@section('content')
<ul class="nav nav-pills mb-3">
    @foreach (['en_attente' => 'À traiter (' . $enAttente . ')', 'acceptee' => 'Acceptés', 'refusee' => 'Refusés'] as $k => $v)
        <li class="nav-item"><a class="nav-link {{ $statut === $k ? 'active' : '' }}" href="{{ route('absences.justifications', ['statut' => $k]) }}">{{ $v }}</a></li>
    @endforeach
</ul>

@if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

@forelse ($absences as $a)
    <div class="card shadow-sm mb-3">
        <div class="card-body row g-3 align-items-start">
            <div class="col-md-4">
                <strong>{{ $a->stagiaire->nom ?? '' }} {{ $a->stagiaire->prenom ?? '' }}</strong>
                <div class="small text-muted">{{ $a->stagiaire->matricule ?? '' }} · {{ $a->stagiaire->classe->nom ?? '' }}</div>
                <div class="mt-2">
                    <span class="badge bg-light text-dark border">{{ $a->date->format('d/m/Y') }}</span>
                    <span class="badge bg-light text-dark border">{{ $a->type_libelle }}</span>
                    @if ($a->planning)<span class="badge bg-light text-dark border">{{ $a->planning->matiere->nom ?? '' }}</span>@endif
                </div>
            </div>
            <div class="col-md-4">
                <div class="small text-muted">Motif du stagiaire (envoyé le {{ optional($a->justification_soumise_at)->format('d/m H:i') }})</div>
                <div>{{ $a->justification_motif }}</div>
                @if ($a->document_justificatif)
                    <a href="{{ asset('storage/' . $a->document_justificatif) }}" target="_blank" class="btn btn-sm btn-outline-secondary mt-2"><i class="fas fa-paperclip me-1"></i>Voir le justificatif</a>
                @endif
            </div>
            <div class="col-md-4">
                @if ($a->justification_statut === 'en_attente')
                    <form method="POST" action="{{ route('absences.justification.traiter', $a) }}">
                        @csrf
                        <input type="text" name="commentaire" class="form-control form-control-sm mb-2" placeholder="Commentaire (obligatoire si refus)">
                        <div class="d-flex gap-2">
                            <button name="decision" value="acceptee" class="btn btn-sm btn-success flex-fill"><i class="fas fa-check me-1"></i>Accepter</button>
                            <button name="decision" value="refusee" class="btn btn-sm btn-outline-danger flex-fill"><i class="fas fa-times me-1"></i>Refuser</button>
                        </div>
                    </form>
                @else
                    <span class="badge bg-{{ $a->justification_statut === 'acceptee' ? 'success' : 'danger' }}">{{ $a->justification_libelle }}</span>
                    <div class="small text-muted">le {{ optional($a->justification_traitee_at)->format('d/m/Y') }}</div>
                    @if ($a->justification_commentaire)<div class="small">{{ $a->justification_commentaire }}</div>@endif
                @endif
            </div>
        </div>
    </div>
@empty
    <div class="alert alert-light border">Aucun justificatif {{ $statut === 'en_attente' ? 'à traiter' : '' }}.</div>
@endforelse

{{ $absences->links() }}
@endsection
