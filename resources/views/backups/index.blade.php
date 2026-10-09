@extends('layouts.app')

@section('title', 'Sauvegardes')
@section('page-title', 'Sauvegardes')

@section('content')
<div class="card shadow-sm mb-4">
    <div class="card-body d-flex flex-wrap gap-2 align-items-center">
        <span class="me-auto text-muted">Créer une nouvelle sauvegarde :</span>
        <button class="btn btn-primary" onclick="creerSauvegarde('full', this)"><i class="fas fa-archive me-1"></i>Complète</button>
        <button class="btn btn-outline-primary" onclick="creerSauvegarde('database', this)"><i class="fas fa-database me-1"></i>Base de données</button>
        <button class="btn btn-outline-primary" onclick="creerSauvegarde('files', this)"><i class="fas fa-folder me-1"></i>Fichiers</button>
    </div>
</div>

<div id="backup-alert"></div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr><th>Nom</th><th>Type</th><th>Taille</th><th>Date</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
                @forelse ($backups as $backup)
                    <tr>
                        <td>{{ $backup['name'] }}</td>
                        <td><span class="badge bg-secondary">{{ $backup['type'] }}</span></td>
                        <td>{{ $backup['size'] }}</td>
                        <td>{{ $backup['date']->format('d/m/Y H:i') }}</td>
                        <td class="text-end">
                            <a href="{{ route('backups.download', $backup['filename']) }}" class="btn btn-sm btn-outline-success">
                                <i class="fas fa-download"></i>
                            </a>
                            <button class="btn btn-sm btn-outline-danger" onclick="supprimerSauvegarde('{{ $backup['filename'] }}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Aucune sauvegarde pour le moment.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    function afficher(message, type) {
        document.getElementById('backup-alert').innerHTML =
            `<div class="alert alert-${type}">${message}</div>`;
    }

    async function creerSauvegarde(type, btn) {
        btn.disabled = true;
        afficher('Sauvegarde en cours…', 'info');
        try {
            const res = await fetch('{{ route('backups.create') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ type })
            });
            const data = await res.json();
            if (data.success) { location.reload(); } else { afficher(data.message, 'danger'); }
        } catch (e) {
            afficher('Erreur réseau : ' + e.message, 'danger');
        } finally {
            btn.disabled = false;
        }
    }

    async function supprimerSauvegarde(filename) {
        if (!confirm('Supprimer cette sauvegarde ?')) return;
        const url = '{{ route('backups.delete', '__FILE__') }}'.replace('__FILE__', encodeURIComponent(filename));
        const res = await fetch(url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } });
        const data = await res.json();
        data.success ? location.reload() : afficher(data.message, 'danger');
    }
</script>
@endpush
