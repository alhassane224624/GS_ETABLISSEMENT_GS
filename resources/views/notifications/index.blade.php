@extends('layouts.app')

@section('title', 'Mes Notifications')
@section('page-title', 'Mes Notifications')

@section('content')
<div class="container-fluid px-3 px-md-4">
    <div class="row">
        <div class="col-12">
            <!-- En-tête avec actions -->
            <div class="card shadow-sm mb-3">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                        <div>
                            <h4 class="mb-2 mb-md-0 fs-5 fs-md-4">
                                <i class="fas fa-bell text-primary"></i> 
                                <span class="d-none d-sm-inline">Mes Notifications</span>
                                <span class="d-inline d-sm-none">Notifications</span>
                                @if($unreadCount > 0)
                                    <span class="badge bg-danger ms-2">{{ $unreadCount }}</span>
                                @endif
                            </h4>
                        </div>
                        
                        <div class="d-flex flex-wrap gap-2 w-100 w-md-auto">
                            @if($unreadCount > 0)
                                <form action="{{ route('notifications.read-all') }}" method="POST" class="flex-fill flex-md-grow-0">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm w-100">
                                        <i class="fas fa-check-double me-1"></i> 
                                        <span class="d-none d-sm-inline">Tout marquer</span>
                                        <span class="d-inline d-sm-none">Marquer</span>
                                    </button>
                                </form>
                            @endif
                            
                            <button type="button" 
                                    class="btn btn-outline-danger btn-sm flex-fill flex-md-grow-0" 
                                    onclick="deleteReadNotifications()">
                                <i class="fas fa-trash me-1"></i> 
                                <span class="d-none d-sm-inline">Supprimer lues</span>
                                <span class="d-inline d-sm-none">Supprimer</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Liste des notifications -->
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    @forelse($notifications as $notification)
                        <div class="notification-item {{ $notification->read_at ? '' : 'unread' }} p-3 p-md-4 border-bottom">
                            <div class="d-flex align-items-start gap-3">
                                <div class="notification-icon {{ $notification->data['type'] ?? 'info' }}">
                                    <i class="{{ $notification->data['icon'] ?? 'fas fa-bell' }}"></i>
                                </div>
                                
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="mb-0 fw-semibold">
                                            {{ $notification->data['title'] ?? 'Notification' }}
                                            @if(!$notification->read_at)
                                                <span class="badge bg-primary ms-2">Nouveau</span>
                                            @endif
                                        </h5>
                                        
                                        <div class="btn-group btn-group-sm">
                                            @if(!$notification->read_at)
                                                <button class="btn btn-outline-primary" 
                                                        onclick="markAsRead('{{ $notification->id }}')"
                                                        title="Marquer comme lu">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            @endif
                                            
                                            @if(isset($notification->data['url']))
                                                <a href="{{ $notification->data['url'] }}" 
                                                   class="btn btn-outline-secondary"
                                                   title="Voir">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                            @endif
                                            
                                            <button class="btn btn-outline-danger" 
                                                    onclick="deleteNotificationInPage('{{ $notification->id }}')"
                                                    title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <p class="mb-2 text-muted">
                                        {{ $notification->data['message'] ?? '' }}
                                    </p>
                                    
                                    <small class="text-muted">
                                        <i class="far fa-clock"></i> 
                                        {{ $notification->created_at->diffForHumans() }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5">
                            <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Aucune notification</h5>
                            <p class="text-muted">Vous êtes à jour !</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Pagination -->
            @if($notifications->hasPages())
                <div class="mt-3 mt-md-4 d-flex justify-content-center">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    .notification-icon {
        width: 42px;
        height: 42px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        flex-shrink: 0;
        font-size: 1.1rem;
    }

    .notification-icon.success { background-color: #d1f2eb; color: #198754; }
    .notification-icon.info { background-color: #cfe2ff; color: #0d6efd; }
    .notification-icon.warning { background-color: #fff3cd; color: #ffc107; }
    .notification-icon.danger { background-color: #f8d7da; color: #dc3545; }

    .notification-item { transition: all 0.2s; }
    .notification-item:hover { background-color: #f8f9fa; }
    .notification-item.unread { background-color: #e7f3ff; border-left: 3px solid #4f46e5; }
</style>

@push('scripts')
<script>
    async function markAsRead(notificationId) {
        try {
            const response = await fetch(`/notifications/${notificationId}/read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            if (response.ok) location.reload();
            else alert('Erreur lors du marquage');
        } catch (error) {
            console.error('Erreur:', error);
            alert('Une erreur est survenue');
        }
    }

    // ✅ MISE À JOUR : compatible InfinityFree (POST au lieu de DELETE)
    async function deleteNotificationInPage(notificationId) {
        if (!confirm('Supprimer cette notification ?')) return;
        try {
            const response = await fetch(`/notifications/${notificationId}/delete`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
            if (response.ok) location.reload();
        } catch (error) {
            console.error('Erreur:', error);
            alert('Une erreur est survenue');
        }
    }

    // ✅ MISE À JOUR : compatible InfinityFree (POST au lieu de DELETE)
    async function deleteReadNotifications() {
        if (!confirm('Supprimer toutes les notifications lues ?')) return;
        try {
            const response = await fetch('{{ route("notifications.delete-read") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
            if (response.ok) location.reload();
        } catch (error) {
            console.error('Erreur:', error);
            alert('Une erreur est survenue');
        }
    }
</script>
@endpush
@endsection
