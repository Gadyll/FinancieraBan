<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'MYBANK')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>

<header class="topbar">
    <div class="container py-3 d-flex align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-2">
            <span class="brand">MYBANK</span>
        </div>

        <nav>
            <ul class="nav-links">
                <li><a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a></li>
                <li><a href="{{ route('clients.index') }}" class="{{ request()->routeIs('clients.*') ? 'active' : '' }}">Clientes</a></li>
                <li><a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}">Usuarios</a></li>
            </ul>
        </nav>

        @php $me = session('mybank_user'); @endphp
        <div class="d-flex align-items-center gap-2">
            <span class="pill">{{ $me['username'] ?? '—' }}</span>
            <span class="pill" style="background:rgba(35,184,91,.12);border-color:rgba(35,184,91,.35)">{{ $me['role'] ?? '—' }}</span>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn-logout" type="submit">Salir</button>
            </form>
        </div>
    </div>
</header>

<main class="page">
    <div class="container">
        @yield('content')
    </div>
</main>

@stack('scripts')
</body>
</html>



