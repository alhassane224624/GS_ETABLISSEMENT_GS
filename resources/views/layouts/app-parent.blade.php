<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Espace parents') — {{ \App\Support\Etablissement::get('nom') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style> body { background: #f5f6fb; } .navbar-brand { font-weight: 700; } </style>
</head>
<body>
<nav class="navbar navbar-expand navbar-dark" style="background: #4f46e5;">
    <div class="container">
        <a class="navbar-brand" href="{{ route('parent.index') }}"><i class="fas fa-user-friends me-2"></i>{{ \App\Support\Etablissement::get('nom') }} — Espace parents</a>
        <div class="d-flex align-items-center gap-3 text-white">
            <span class="d-none d-md-inline small">{{ auth()->user()->name }}</span>
            <a href="{{ route('profile.edit') }}" class="text-white" title="Mon compte / mot de passe"><i class="fas fa-user-cog"></i></a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-sm btn-light">Déconnexion</button></form>
        </div>
    </div>
</nav>
<main class="container py-4">
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @yield('content')
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
