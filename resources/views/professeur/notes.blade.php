@extends('layouts.app-professeur')

@section('content')
    <div class="container py-5">
        <h1 class="fw-bold text-primary">
            <i class="fas fa-book me-2"></i> Notes de {{ $stagiaire->nom }} {{ $stagiaire->prenom }}
        </h1>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Formulaire pour ajouter une note -->
        <div class="card mb-4">
            <div class="card-body">
                <h3 class="card-title">Ajouter une Note</h3>
                {{-- ✅ CORRECTION: Utiliser la bonne route --}}
                <form action="{{ route('professeur.stagiaires.notes.store', $stagiaire) }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="matiere_id" class="form-label">Matière</label>
                            <select name="matiere_id" id="matiere_id" class="form-control @error('matiere_id') is-invalid @enderror" required>
                                <option value="">Sélectionner une matière</option>
                                @foreach ($matieres as $matiere)
                                    <option value="{{ $matiere->id }}" {{ old('matiere_id') == $matiere->id ? 'selected' : '' }}>
                                        {{ $matiere->nom }}
                                    </option>
                                @endforeach
                            </select>
                            @error('matiere_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="note" class="form-label">Note</label>
                            <input type="number" name="note" id="note" 
                                   class="form-control @error('note') is-invalid @enderror" 
                                   value="{{ old('note') }}" 
                                   min="0" max="20" step="0.01" required>
                            @error('note')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="type_note" class="form-label">Type</label>
                            <select name="type_note" id="type_note" class="form-control @error('type_note') is-invalid @enderror" required>
                                <option value="ds" {{ old('type_note') == 'ds' ? 'selected' : '' }}>DS</option>
                                <option value="cc" {{ old('type_note') == 'cc' ? 'selected' : '' }}>CC</option>
                                <option value="examen" {{ old('type_note') == 'examen' ? 'selected' : '' }}>Examen</option>
                                <option value="tp" {{ old('type_note') == 'tp' ? 'selected' : '' }}>TP</option>
                                <option value="projet" {{ old('type_note') == 'projet' ? 'selected' : '' }}>Projet</option>
                            </select>
                            @error('type_note')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="note_sur" class="form-label">Note sur</label>
                            <input type="number" name="note_sur" id="note_sur" 
                                   class="form-control" 
                                   value="{{ old('note_sur', 20) }}" 
                                   min="1" max="100" step="0.5">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="commentaire" class="form-label">Commentaire (optionnel)</label>
                        <textarea name="commentaire" id="commentaire" 
                                  class="form-control" 
                                  rows="2">{{ old('commentaire') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i> Ajouter la note
                    </button>
                </form>
            </div>
        </div>

        <!-- Liste des notes -->
        <div class="card mb-4">
            <div class="card-body">
                <h3 class="card-title">Liste des Notes</h3>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Matière</th>
                                <th>Note</th>
                                <th>Type</th>
                                <th>Commentaire</th>
                                <th>Créée par</th>
                                <th>Date</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($notes as $note)
                                <tr>
                                    <td>
                                        <strong>{{ $note->matiere->nom }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $note->matiere->code }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $note->note_sur_20 >= 10 ? 'success' : 'danger' }} fs-6">
                                            {{ $note->note }}/{{ $note->note_sur }}
                                        </span>
                                        @unless ($note->estPubliee())
                                            <span class="badge bg-light text-muted border" title="Visible par le stagiaire après publication des résultats"><i class="fas fa-eye-slash me-1"></i>Non publiée</span>
                                        @endunless
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ strtoupper($note->type_note) }}</span>
                                    </td>
                                    <td>
                                        <small>{{ Str::limit($note->commentaire, 50) }}</small>
                                    </td>
                                    <td>{{ $note->creator->name ?? 'N/A' }}</td>
                                    <td>
                                        <small>{{ $note->created_at->format('d/m/Y H:i') }}</small>
                                    </td>
                                    <td class="text-center">
                                        {{-- ✅ CORRECTION: Utiliser la bonne route pour update --}}
                                        <button type="button" class="btn btn-sm btn-warning" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editNoteModal{{ $note->id }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <form action="{{ route('notes.destroy', $note) }}" 
                                              method="POST" 
                                              class="d-inline" 
                                              onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette note ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>

                                <!-- Modal de modification -->
                                <div class="modal fade" id="editNoteModal{{ $note->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Modifier la note</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            {{-- ✅ CORRECTION: Utiliser la bonne route --}}
                                            <form action="{{ route('professeur.stagiaires.notes.update', [$stagiaire, $note]) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Matière</label>
                                                        <select name="matiere_id" class="form-control" required>
                                                            @foreach ($matieres as $matiere)
                                                                <option value="{{ $matiere->id }}" 
                                                                        {{ $note->matiere_id == $matiere->id ? 'selected' : '' }}>
                                                                    {{ $matiere->nom }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Note</label>
                                                        <input type="number" name="note" class="form-control" 
                                                               value="{{ $note->note }}" 
                                                               min="0" max="20" step="0.01" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Type</label>
                                                        <select name="type_note" class="form-control" required>
                                                            <option value="ds" {{ $note->type_note == 'ds' ? 'selected' : '' }}>DS</option>
                                                            <option value="cc" {{ $note->type_note == 'cc' ? 'selected' : '' }}>CC</option>
                                                            <option value="examen" {{ $note->type_note == 'examen' ? 'selected' : '' }}>Examen</option>
                                                            <option value="tp" {{ $note->type_note == 'tp' ? 'selected' : '' }}>TP</option>
                                                            <option value="projet" {{ $note->type_note == 'projet' ? 'selected' : '' }}>Projet</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Commentaire</label>
                                                        <textarea name="commentaire" class="form-control" rows="2">{{ $note->commentaire }}</textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-save me-1"></i> Enregistrer
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="fas fa-exclamation-circle me-2"></i> Aucune note enregistrée.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <a href="{{ route('professeur.stagiaires') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i> Retour aux stagiaires
        </a>
    </div>
@endsection