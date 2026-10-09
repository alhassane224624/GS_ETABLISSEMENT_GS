@extends('layouts.app-parent')
@section('title', 'Mes enfants')

@section('content')
<h4 class="mb-4">Mes enfants</h4>
<div class="row g-3">
    @forelse ($enfants as $e)
        <div class="col-md-6">
            <a href="{{ route('parent.enfant', $e) }}" class="card shadow-sm text-decoration-none text-dark h-100">
                <div class="card-body">
                    <h5 class="mb-1">{{ $e->prenom }} {{ $e->nom }}</h5>
                    <div class="text-muted small mb-3">{{ $e->filiere->nom ?? '' }} · {{ $e->classe->nom ?? 'sans classe' }}</div>
                    <div class="row text-center">
                        <div class="col"><div class="small text-muted">Dernière moyenne</div><strong>{{ $e->resume['dernier_bulletin'] ? number_format($e->resume['dernier_bulletin']->moyenne_generale, 2, ',', '') : '—' }}</strong></div>
                        <div class="col"><div class="small text-muted">Absences non justifiées</div><strong class="{{ $e->resume['absences_non_justifiees'] ? 'text-danger' : '' }}">{{ $e->resume['absences_non_justifiees'] }}</strong></div>
                        <div class="col"><div class="small text-muted">Reste à payer</div><strong class="{{ $e->resume['retards'] ? 'text-danger' : '' }}">{{ number_format($e->resume['reste'], 2, ',', ' ') }} DH</strong></div>
                    </div>
                </div>
            </a>
        </div>
    @empty
        <div class="col-12"><div class="alert alert-info">Aucun enfant n'est rattaché à votre compte. Contactez l'établissement.</div></div>
    @endforelse
</div>
@endsection
