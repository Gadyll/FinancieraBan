@php
  $is = fn($name) => request()->routeIs($name) ? 'active nav-pill-active' : '';
@endphp

<div class="app-bg rounded-4 p-3">
  <div class="small text-uppercase muted-2 fw-semibold mb-2" style="letter-spacing:.12em;">Navegación</div>

  <div class="d-grid gap-2">
    <a class="sidebar-link {{ $is('dashboard') }}" href="{{ route('dashboard') }}">
      <span class="sidebar-dot"></span> Dashboard
    </a>

    <a class="sidebar-link {{ $is('clients.*') }}" href="{{ route('clients.index') }}">
      <span class="sidebar-dot"></span> Clientes
    </a>

    <a class="sidebar-link {{ $is('users.*') }}" href="{{ route('users.index') }}">
      <span class="sidebar-dot"></span> Cobradores
    </a>
  </div>
</div>
