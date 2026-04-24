<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'MYBANK Admin')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>

{{-- ══════════════ TOPBAR ══════════════ --}}
<header class="topbar">
    <div class="topbar-inner">

        {{-- Brand --}}
        <a href="{{ route('dashboard') }}" class="brand">
            <div class="brand-icon">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
            </div>
            MYBANK
        </a>

        {{-- Hamburger (móvil) --}}
        <button class="nav-toggle" id="navToggle" type="button" aria-label="Menú">
            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        {{-- Navegación --}}
        <nav id="mainNav">
            <ul class="nav-links">
                <li>
                    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                            <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
                        </svg>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="{{ route('clients.index') }}" class="{{ request()->routeIs('clients.*') ? 'active' : '' }}">
                        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
                        </svg>
                        Clientes
                    </a>
                </li>
                <li>
                    <a href="{{ route('loans.index') }}" class="{{ request()->routeIs('loans.*') ? 'active' : '' }}">
                        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Préstamos
                    </a>
                </li>
                <li>
                    <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}">
                        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="8" r="4"/><path stroke-linecap="round" d="M4 20v-1a5 5 0 015-5h6a5 5 0 015 5v1"/>
                        </svg>
                        Usuarios
                    </a>
                </li>
            </ul>
        </nav>

        {{-- Usuario --}}
        @php $me = session('mybank_user'); @endphp
        <div class="topbar-user">
            <span class="user-chip">
                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="8" r="4"/><path stroke-linecap="round" d="M4 20v-1a5 5 0 015-5h6a5 5 0 015 5v1"/>
                </svg>
                {{ $me['username'] ?? '—' }}
            </span>
            <span class="role-chip">{{ $me['role'] ?? '—' }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn-logout" type="submit">
                    <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Salir
                </button>
            </form>
        </div>

    </div>
</header>

{{-- ══════════════ MAIN ══════════════ --}}
<main class="page">
    <div class="container">
        @yield('content')
    </div>
</main>

@stack('scripts')
<script>
(function(){
    var btn = document.getElementById('navToggle');
    var nav = document.getElementById('mainNav');
    if(btn && nav){
        btn.addEventListener('click', function(){
            nav.classList.toggle('open');
        });
        // Cerrar al hacer clic fuera
        document.addEventListener('click', function(e){
            if(!nav.contains(e.target) && e.target !== btn && !btn.contains(e.target)){
                nav.classList.remove('open');
            }
        });
    }
})();
</script>
</body>
</html>
