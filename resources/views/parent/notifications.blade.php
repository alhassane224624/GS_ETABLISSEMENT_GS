@extends('layouts.app-parent')
@section('title', 'Notifications')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-bell me-2 text-primary"></i>Notifications</h4>
    <a href="{{ route('parent.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Retour</a>
</div>

<div class="list-group shadow-sm">
    @forelse ($notifications as $n)
        @php $d = $n->data; @endphp
        <a href="{{ $d['url'] ?? route('parent.index') }}" class="list-group-item list-group-item-action d-flex gap-3 {{ $n->read_at ? '' : 'bg-light' }}">
            <i class="{{ $d['icon'] ?? 'fas fa-bell' }} fa-lg mt-1 text-{{ ['success' => 'success', 'warning' => 'warning', 'danger' => 'danger'][$d['type'] ?? ''] ?? 'primary' }}"></i>
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between gap-2">
                    <strong>{{ $d['title'] ?? 'Notification' }}</strong>
                    <small class="text-muted text-nowrap">{{ $n->created_at->diffForHumans() }}</small>
                </div>
                <div class="small">{{ $d['message'] ?? '' }}</div>
            </div>
            @unless ($n->read_at)<span class="badge bg-primary align-self-center">Nouveau</span>@endunless
        </a>
    @empty
        <div class="list-group-item text-center text-muted py-5">
            <i class="fas fa-bell-slash fa-2x mb-2 d-block"></i>
            Aucune notification pour le moment. Vous serez prévenu ici des absences, des nouveaux résultats et des bulletins.
        </div>
    @endforelse
</div>

<div class="mt-3">{{ $notifications->links() }}</div>
@endsection
