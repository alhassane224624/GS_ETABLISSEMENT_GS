@extends('layouts.app')

@section('title', 'Filières du professeur')
@section('page-title', 'Affectation des filières')

@section('content')
<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="fas fa-layer-group me-2 text-primary"></i>Filières de {{ $professeur->name }}</h5>
    </div>
    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('professeurs.filieres.update', $professeur) }}">
            @csrf
            @method('PUT')

            <div class="row">
                @foreach ($filieres as $filiere)
                    <div class="col-md-4 mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="filieres[]"
                                   value="{{ $filiere->id }}" id="filiere{{ $filiere->id }}"
                                   @checked($professeur->filieres->contains($filiere->id))>
                            <label class="form-check-label" for="filiere{{ $filiere->id }}">{{ $filiere->nom }}</label>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Enregistrer</button>
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Retour</a>
            </div>
        </form>
    </div>
</div>
@endsection
