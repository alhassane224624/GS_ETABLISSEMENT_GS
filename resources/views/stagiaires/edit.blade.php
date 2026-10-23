@extends('layouts.app')

@section('title', 'Modifier ' . $stagiaire->prenom . ' ' . $stagiaire->nom)
@section('page-title', 'Modifier un stagiaire')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0">{{ $stagiaire->prenom }} {{ $stagiaire->nom }} <span class="badge bg-secondary">{{ $stagiaire->matricule }}</span></h5>
    <a href="{{ route('stagiaires.show', $stagiaire) }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Retour à la fiche</a>
</div>

<form action="{{ route('stagiaires.update', $stagiaire) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @include('stagiaires._form', ['stagiaire' => $stagiaire])
</form>
@endsection
