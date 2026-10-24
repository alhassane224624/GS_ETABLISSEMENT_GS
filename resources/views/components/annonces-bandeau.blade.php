{{-- Annonces importantes ou urgentes non lues : rappelées en haut de chaque page jusqu'à leur lecture --}}
@auth
    @php $aSignaler = request()->routeIs('annonces.show') ? collect() : \App\Models\Annonce::aSignaler(auth()->user()); @endphp
    @foreach ($aSignaler->take(3) as $a)
        <div class="alert alert-{{ $a->couleur }} d-flex flex-wrap justify-content-between align-items-center gap-2 shadow-sm">
            <span>
                <i class="fas {{ $a->icone }} me-2"></i>
                <strong>{{ $a->importance === 'urgent' ? 'Annonce urgente' : 'Annonce' }} :</strong> {{ $a->titre }}
            </span>
            <a href="{{ route('annonces.show', $a) }}" class="btn btn-sm {{ $a->importance === 'urgent' ? 'btn-danger' : 'btn-dark' }}">Lire</a>
        </div>
    @endforeach
    @if ($aSignaler->count() > 3)
        <div class="small mb-3"><a href="{{ route('annonces.index') }}">{{ $aSignaler->count() - 3 }} autre(s) annonce(s) à lire</a></div>
    @endif
@endauth
