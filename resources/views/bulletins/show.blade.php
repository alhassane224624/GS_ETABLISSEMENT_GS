@extends('layouts.app')

@section('title', 'Détails du Bulletin')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-file-alt"></i>
                    Bulletin de {{ $bulletin->stagiaire->nom }} {{ $bulletin->stagiaire->prenom }}
                </h2>
                <div>
                    <a href="{{ route('bulletins.download-pdf', $bulletin) }}" class="btn btn-danger" target="_blank">
                        <i class="fas fa-file-pdf"></i> Télécharger PDF
                    </a>
                    <a href="{{ route('bulletins.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-user"></i> Informations Générales</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Stagiaire :</strong> {{ $bulletin->stagiaire->nom }} {{ $bulletin->stagiaire->prenom }}</p>
                    <p><strong>Matricule :</strong> {{ $bulletin->stagiaire->matricule }}</p>
                    <p><strong>Classe :</strong> {{ $bulletin->classe->nom }} - {{ $bulletin->classe->niveau->nom ?? 'N/A' }}</p>
                    <p><strong>Filière :</strong> {{ $bulletin->classe->filiere->nom ?? 'N/A' }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Période :</strong> {{ $bulletin->periode->nom }}</p>
                    <p><strong>Moyenne Générale :</strong> 
                        <span class="badge badge-{{ $bulletin->moyenne_generale >= 10 ? 'success' : 'danger' }} badge-lg">
                            {{ number_format($bulletin->moyenne_generale, 2) }}/20
                        </span>
                    </p>
                    <p><strong>Rang :</strong> 
                        <span class="badge badge-primary">
                            {{ $bulletin->rang }}{{ $bulletin->rang == 1 ? 'er' : 'ème' }} / {{ $bulletin->total_classe }}
                        </span>
                    </p>
                    <p><strong>Statut :</strong>
                        @if($bulletin->validated_at)
                            <span class="badge badge-success">
                                <i class="fas fa-check"></i> Validé le {{ $bulletin->validated_at->format('d/m/Y') }}
                            </span>
                        @else
                            <span class="badge badge-warning">
                                <i class="fas fa-clock"></i> En attente de validation
                            </span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-book"></i> Moyennes par Matière</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th>Matière</th>
                            <th class="text-center">Code</th>
                            <th class="text-center">Coefficient</th>
                            <th class="text-center">Moyenne</th>
                            <th class="text-center">Points</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalPoints = 0;
                            $totalCoef = 0;
                            // ✅ S'assurer que moyennes_matieres est un tableau
                            $moyennes = is_array($bulletin->moyennes_matieres) 
                                ? $bulletin->moyennes_matieres 
                                : json_decode($bulletin->moyennes_matieres, true) ?? [];
                        @endphp
                        
                        @forelse ($moyennes as $matiere)
                            @php
                                // ✅ Vérification que $matiere est un tableau et contient les clés nécessaires
                                if (!is_array($matiere)) continue;
                                
                                $moyenne = $matiere['moyenne'] ?? 0;
                                $coefficient = $matiere['coefficient'] ?? 1;
                                $points = $moyenne * $coefficient;
                                $totalPoints += $points;
                                $totalCoef += $coefficient;
                                
                                // Déterminer la classe de couleur
                                $badgeClass = 'secondary';
                                if ($moyenne >= 16) $badgeClass = 'success';
                                elseif ($moyenne >= 14) $badgeClass = 'info';
                                elseif ($moyenne >= 12) $badgeClass = 'primary';
                                elseif ($moyenne >= 10) $badgeClass = 'warning';
                                else $badgeClass = 'danger';
                            @endphp
                            <tr>
                                <td><strong>{{ $matiere['matiere'] ?? 'N/A' }}</strong></td>
                                <td class="text-center">{{ $matiere['code'] ?? '-' }}</td>
                                <td class="text-center">{{ $coefficient }}</td>
                                <td class="text-center">
                                    <span class="badge badge-{{ $badgeClass }}">
                                        {{ number_format($moyenne, 2) }}/20
                                    </span>
                                </td>
                                <td class="text-center">{{ number_format($points, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">Aucune note disponible</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($moyennes) > 0)
                    <tfoot class="bg-light">
                        <tr>
                            <th colspan="2">TOTAL</th>
                            <th class="text-center">{{ $totalCoef }}</th>
                            <th class="text-center">
                                <span class="badge badge-{{ $bulletin->moyenne_generale >= 10 ? 'success' : 'danger' }} badge-lg">
                                    {{ number_format($bulletin->moyenne_generale, 2) }}/20
                                </span>
                            </th>
                            <th class="text-center">{{ number_format($totalPoints, 2) }}</th>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0"><i class="fas fa-comment-alt"></i> Appréciation Générale</h5>
        </div>
        <div class="card-body">
            <p class="lead">{{ $bulletin->appreciation_generale ?? 'Aucune appréciation' }}</p>
        </div>
    </div>

    @if(!$bulletin->validated_at)
        <div class="mt-4">
            <form action="{{ route('bulletins.validate', $bulletin) }}" method="POST" 
                  onsubmit="return confirm('Valider ce bulletin ? Le stagiaire pourra le consulter et ses notes de la période seront verrouillées.')">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="fas fa-check"></i> Valider ce Bulletin
                </button>
            </form>
        </div>
    @else
        <div class="mt-4 card border-warning">
            <div class="card-body">
                <p class="mb-2"><i class="fas fa-lock text-warning me-1"></i>Bulletin validé : les notes de cette période sont verrouillées.</p>
                <form action="{{ route('bulletins.invalidate', $bulletin) }}" method="POST" class="d-flex gap-2"
                      onsubmit="return confirm('Rouvrir ce bulletin ? Le stagiaire ne le verra plus jusqu\'à la nouvelle validation.')">
                    @csrf
                    @method('PATCH')
                    <input type="text" name="motif" class="form-control" placeholder="Motif (ex. erreur de note en mathématiques)" required>
                    <button type="submit" class="btn btn-outline-warning text-nowrap"><i class="fas fa-unlock me-1"></i>Rouvrir</button>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection