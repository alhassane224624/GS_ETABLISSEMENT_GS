@extends('layouts.app')

@section('title', 'Matières du professeur')
@section('page-title', 'Affectation des matières')

@section('content')
@php
    $filiereChoisie = (int) request('filiere_id', old('filiere_id', $filieres->first()->id ?? 0));
    $dejaAffectees = $professeur->matieresEnseignees
        ->filter(fn ($m) => (int) $m->pivot->filiere_id === $filiereChoisie)
        ->pluck('id');
@endphp

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="fas fa-book me-2 text-primary"></i>Matières de {{ $professeur->name }}</h5>
    </div>
    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        {{-- Choix de la filière (recharge la page) --}}
        <form method="GET" class="mb-4">
            <label class="form-label">Filière</label>
            <select name="filiere_id" class="form-select" onchange="this.form.submit()">
                @foreach ($filieres as $filiere)
                    <option value="{{ $filiere->id }}" @selected($filiere->id === $filiereChoisie)>{{ $filiere->nom }}</option>
                @endforeach
            </select>
        </form>

        <form method="POST" action="{{ route('professeurs.matieres.update', $professeur) }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="filiere_id" value="{{ $filiereChoisie }}">

            <div class="row">
                @foreach ($matieres as $matiere)
                    <div class="col-md-4 mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="matieres[]"
                                   value="{{ $matiere->id }}" id="matiere{{ $matiere->id }}"
                                   @checked($dejaAffectees->contains($matiere->id))>
                            <label class="form-check-label" for="matiere{{ $matiere->id }}">
                                {{ $matiere->nom }} <small class="text-muted">(coef. {{ $matiere->coefficient }})</small>
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Enregistrer pour cette filière</button>
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Retour</a>
            </div>
        </form>
    </div>
</div>
@endsection
