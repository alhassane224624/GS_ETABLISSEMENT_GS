@extends('layouts.app')

@section('title', 'Ajouter un stagiaire')
@section('page-title', 'Ajouter un stagiaire')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <p class="text-muted mb-0">Les champs marqués <span class="text-danger">*</span> sont obligatoires. Un compte de connexion est créé automatiquement.</p>
    <a href="{{ route('stagiaires.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Retour à la liste</a>
</div>

<form action="{{ route('stagiaires.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @include('stagiaires._form', ['stagiaire' => null])
</form>
@endsection
