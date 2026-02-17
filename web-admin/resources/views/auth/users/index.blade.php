@extends('layouts.app')

@section('title', 'Usuarios - MYBANK')

@section('content')
@php
    $errorText = $error ?? null;
@endphp

<div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
    <div>
        <h1 class="page-title mb-1">Usuarios (Cobradores)</h1>
        <p class="page-sub mb-0">Crea y administra cobradores que usarán la app móvil.</p>
    </div>

    <a href="{{ route('dashboard') }}" class="btn btn-outline-light">Volver al Dashboard</a>
</div>

@if($errorText)
    <div class="alert alert-danger mb-3">{{ $errorText }}</div>
@endif

@if(session('success'))
    <div class="alert mb-3">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger mb-3">
        {{ $errors->first() }}
    </div>
@endif

<div class="row g-3">
    {{-- Crear cobrador --}}
    <div class="col-12 col-lg-5">
        <div class="surface surface-pad">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h2 class="h4 mb-0">Crear cobrador</h2>
                <span class="pill">ROL: USER</span>
            </div>
            <p class="help mb-3">Solo se crean usuarios con rol <b>USER</b>.</p>

            <form method="POST" action="{{ route('users.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input class="form-control" name="username" value="{{ old('username') }}" placeholder="cobrador1" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email (opcional)</label>
                    <input class="form-control" name="email" value="{{ old('email') }}" placeholder="cobrador@dominio.com">
                    <div class="help mt-1">Tip: usa un email real (no .local) para evitar validación estricta.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input class="form-control" name="password" type="password" placeholder="••••••••" required>
                    <div class="help mt-1">Máximo 72 caracteres (bcrypt).</div>
                </div>

                <button class="btn btn-primary w-100" type="submit">Crear</button>
            </form>
        </div>
    </div>

    {{-- Lista de usuarios --}}
    <div class="col-12 col-lg-7">
        <div class="surface surface-pad">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <div>
                    <h2 class="h4 mb-0">Lista de usuarios</h2>
                    <div class="help">ADMIN no se debe tocar. USER se puede activar/desactivar o eliminar.</div>
                </div>
            </div>

            <div class="table-wrap mt-3">
                <table class="table table-clean table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width:80px">ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th style="width:110px">Role</th>
                            <th style="width:110px">Activo</th>
                            <th class="text-end" style="width:240px">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $u)
                            @php
                                $role = $u['role'] ?? '—';
                                $isAdmin = ($role === 'ADMIN');
                                $isActive = (bool)($u['is_active'] ?? false);
                                $uid = $u['id'] ?? null; // ✅ puede venir null si la API falla
                            @endphp

                            <tr>
                                <td class="mono">{{ $uid ?? '—' }}</td>
                                <td class="fw-semibold">{{ $u['username'] ?? '—' }}</td>
                                <td class="text-truncate" style="max-width:220px">{{ $u['email'] ?? '—' }}</td>
                                <td><span class="pill">{{ $role }}</span></td>
                                <td>
                                    @if($isActive)
                                        <span class="pill" style="background:rgba(35,184,91,.12);border-color:rgba(35,184,91,.35)">Sí</span>
                                    @else
                                        <span class="pill" style="background:rgba(255,91,91,.10);border-color:rgba(255,91,91,.30)">No</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if(!$uid)
                                        <span class="help">Sin ID (API)</span>
                                    @elseif($isAdmin)
                                        <span class="help">Protegido</span>
                                    @else
                                        {{-- ✅ ARREGLO: route param debe llamarse userId --}}
                                        <form class="d-inline" method="POST" action="{{ route('users.toggle', ['userId' => $uid]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-sm btn-outline-light" type="submit">
                                                {{ $isActive ? 'Desactivar' : 'Activar' }}
                                            </button>
                                        </form>

                                        {{-- ✅ ARREGLO: route param debe llamarse userId --}}
                                        <form class="d-inline" method="POST" action="{{ route('users.destroy', ['userId' => $uid]) }}"
                                              onsubmit="return confirm('¿Eliminar usuario? Esto lo dejará inactivo.')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" type="submit">
                                                Eliminar
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 help">Sin usuarios</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>
@endsection




