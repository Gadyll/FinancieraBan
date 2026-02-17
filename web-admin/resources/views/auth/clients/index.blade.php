@extends('layouts.app')

@section('title', 'Clientes - MYBANK')

@section('content')
@php
    $errorText = $error ?? null;
@endphp

<div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
    <div>
        <h1 class="page-title mb-1">Clientes</h1>
        <p class="page-sub mb-0">Crea, edita y asigna clientes a cobradores.</p>
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
    {{-- Crear cliente --}}
    <div class="col-12 col-lg-5">
        <div class="surface surface-pad">
            <h2 class="h4 mb-2">Crear cliente</h2>
            <p class="help mb-3">Teléfono obligatorio (10 dígitos).</p>

            <form method="POST" action="{{ route('clients.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Número de cliente</label>
                    <input class="form-control" name="client_number" value="{{ old('client_number') }}" placeholder="C0001" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Nombre completo</label>
                    <input class="form-control" name="full_name" value="{{ old('full_name') }}" placeholder="Juan Pérez" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Teléfono (10 dígitos)</label>
                    <input class="form-control" name="phone" value="{{ old('phone') }}" placeholder="4421234567" required>
                </div>

                <button class="btn btn-primary w-100" type="submit">Crear</button>
            </form>
        </div>
    </div>

    {{-- Lista de clientes --}}
    <div class="col-12 col-lg-7">
        <div class="surface surface-pad">
            <h2 class="h4 mb-1">Lista de clientes</h2>
            <p class="help mb-3">Editar datos o asignar a un cobrador.</p>

            <div class="table-wrap">
                <table class="table table-clean table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width:80px">ID</th>
                            <th>Número</th>
                            <th>Nombre</th>
                            <th style="width:150px">Teléfono</th>
                            <th style="width:220px">Asignación</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clients as $c)
                            @php
                                $cid = $c['id'] ?? null; // ✅ evita romper si no hay id
                            @endphp
                            <tr>
                                <td class="mono">{{ $cid ?? '—' }}</td>
                                <td class="fw-semibold">{{ $c['client_number'] ?? '—' }}</td>
                                <td>{{ $c['full_name'] ?? '—' }}</td>
                                <td class="text-nowrap">{{ $c['phone'] ?? '—' }}</td>

                                <td>
                                    @if(!$cid)
                                        <span class="help">Sin ID (API)</span>
                                    @else
                                        {{-- ✅ ARREGLO: route param debe llamarse clientId --}}
                                        <form method="POST" action="{{ route('clients.assign', ['clientId' => $cid]) }}" class="d-flex gap-2">
                                            @csrf

                                            <select name="user_id" class="form-select form-select-sm" required>
                                                <option value="" selected>Selecciona cobrador</option>
                                                @foreach($collectors as $u)
                                                    <option value="{{ $u['id'] }}">
                                                        {{ $u['username'] }} (ID {{ $u['id'] }})
                                                    </option>
                                                @endforeach
                                            </select>

                                            <button class="btn btn-sm btn-outline-light" type="submit">
                                                Asignar
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 help">Sin clientes</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>
@endsection


