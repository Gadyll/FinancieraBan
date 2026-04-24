@extends('layouts.app')
@section('title', 'Clientes — MYBANK')

@push('styles')
<style>
/* Formulario lateral */
.form-card {
    background: #fff; border: 1px solid rgba(26,111,207,.12);
    border-radius: 14px; box-shadow: 0 4px 20px rgba(13,27,46,.07);
    overflow: hidden; position: sticky; top: 80px;
}
.form-card-head {
    background: linear-gradient(135deg, var(--blue), var(--blue-dark));
    color: #fff; padding: 1rem 1.25rem;
}
.form-card-title { font-weight: 900; font-size: 1rem; margin: 0; }
.form-card-sub   { font-size: .83rem; opacity: .80; margin-top: .2rem; }
.form-card-body  { padding: 1.25rem; }

/* Sección dentro del form */
.form-section {
    font-size: .72rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: .10em; color: var(--blue);
    padding: .5rem 0 .4rem;
    border-bottom: 2px solid rgba(26,111,207,.12);
    margin-bottom: .85rem; margin-top: 1rem;
    display: flex; align-items: center; gap: .5rem;
}
.form-section:first-child { margin-top: 0; }
.form-section svg { opacity: .7; }

/* Tabla clientes */
.client-num  { font-weight: 800; font-family: 'Courier New', monospace; color: var(--blue); font-size: .88rem; }
.client-name { font-weight: 700; }
.client-sub  { font-size: .80rem; color: var(--muted); margin-top: .1rem; }

/* Assign form inline */
.assign-inline {
    display: flex; gap: .4rem; align-items: center; flex-wrap: wrap;
}
.assign-inline select {
    flex: 1; min-width: 130px; padding: .38rem .65rem;
    border: 1.5px solid rgba(26,111,207,.18); border-radius: 8px;
    background: #fafcff; font-family: 'Outfit', sans-serif;
    font-size: .83rem; color: var(--text); outline: none;
}
.assign-inline select:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 2px rgba(26,111,207,.10);
}

/* Loan status */
.loan-st { font-size: .76rem; font-weight: 700; }

/* Search bar */
.search-bar {
    padding: .85rem 1.25rem; border-bottom: 1px solid rgba(26,111,207,.10);
    background: #fafcff; display: flex; gap: .65rem; align-items: center; flex-wrap: wrap;
}
</style>
@endpush

@section('content')
@php
    $maritalOptions = $maritalOptions ?? ['SOLTERO','CASADO','UNION LIBRE','VIUDO','DIVORCIADO'];
@endphp

<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <span class="breadcrumb-sep">›</span>
            Clientes
        </div>
        <h1 class="page-title">Clientes</h1>
        <p class="page-sub">{{ count($clients) }} cliente(s) registrado(s). Registra o asigna cobradores aquí.</p>
    </div>
</div>

{{-- Mensajes --}}
@if(!empty($error))
    <div class="alert alert-danger">⚠ {{ $error }}</div>
@endif
@if(session('success'))
    <div class="alert alert-success">✓ {{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row g-3">

    {{-- ══════ FORMULARIO ══════ --}}
    <div class="col-12 col-xl-4">
        <div class="form-card">
            <div class="form-card-head">
                <div class="form-card-title">
                    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" style="display:inline;vertical-align:middle;margin-right:.35rem;">
                        <path stroke-linecap="round" d="M12 5v14M5 12h14"/>
                    </svg>
                    Registrar nuevo cliente
                </div>
                <div class="form-card-sub">El número de cliente se asigna automáticamente.</div>
            </div>
            <div class="form-card-body">
                <form method="POST" action="{{ route('clients.store') }}" autocomplete="off">
                    @csrf

                    <div class="form-section">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="8" r="4"/><path stroke-linecap="round" d="M4 20v-1a5 5 0 015-5h6a5 5 0 015 5v1"/>
                        </svg>
                        Datos personales
                    </div>

                    <div class="field">
                        <label class="field-label field-required">Nombre completo</label>
                        <input class="field-input" name="full_name" value="{{ old('full_name') }}" maxlength="150" required placeholder="Juan Pérez García">
                    </div>

                    <div class="grid-2">
                        <div class="field">
                            <label class="field-label field-required">Teléfono</label>
                            <input class="field-input digits-only" name="phone" value="{{ old('phone') }}"
                                   placeholder="4421234567" maxlength="10" inputmode="numeric" required>
                            <div class="field-hint">10 dígitos</div>
                        </div>
                        <div class="field">
                            <label class="field-label field-required">Estado civil</label>
                            <select class="field-input field-select" name="marital_status" required>
                                <option value="">— Seleccionar —</option>
                                @foreach($maritalOptions as $op)
                                    <option value="{{ $op }}" @selected(old('marital_status') === $op)>{{ $op }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="field">
                        <label class="field-label field-required">Dirección</label>
                        <input class="field-input" name="address" value="{{ old('address') }}" maxlength="255" required placeholder="Calle, Número, Colonia, Ciudad">
                    </div>

                    <div class="field">
                        <label class="field-label">Nombre del cónyuge</label>
                        <input class="field-input" name="spouse_full_name" value="{{ old('spouse_full_name') }}" maxlength="150" placeholder="(opcional)">
                    </div>

                    <div class="grid-3">
                        <div class="field">
                            <label class="field-label">Fecha nacimiento</label>
                            <input class="field-input" type="date" name="birth_date" value="{{ old('birth_date') }}" max="{{ date('Y-m-d', strtotime('-18 years')) }}">
                        </div>
                        <div class="field">
                            <label class="field-label">Ocupación</label>
                            <input class="field-input" name="occupation" value="{{ old('occupation') }}" maxlength="100" placeholder="Empleado...">
                        </div>
                        <div class="field">
                            <label class="field-label">Ingreso mensual</label>
                            <input class="field-input" type="number" name="monthly_income" value="{{ old('monthly_income') }}" min="0" step="100" placeholder="$0.00">
                        </div>
                    </div>

                    <div class="form-section">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        Datos del AVAL (obligatorio)
                    </div>

                    <div class="field">
                        <label class="field-label field-required">Nombre completo del aval</label>
                        <input class="field-input" name="guarantor_full_name" value="{{ old('guarantor_full_name') }}" maxlength="150" required placeholder="Nombre del garante">
                    </div>

                    <div class="field">
                        <label class="field-label field-required">Dirección del aval</label>
                        <input class="field-input" name="guarantor_address" value="{{ old('guarantor_address') }}" maxlength="255" required placeholder="Dirección del garante">
                    </div>

                    <div class="grid-2">
                        <div class="field">
                            <label class="field-label field-required">Teléfono del aval</label>
                            <input class="field-input digits-only" name="guarantor_phone" value="{{ old('guarantor_phone') }}"
                                   placeholder="4429876543" maxlength="10" inputmode="numeric" required>
                        </div>
                        <div class="field">
                            <label class="field-label field-required">Estado civil del aval</label>
                            <select class="field-input field-select" name="guarantor_marital_status" required>
                                <option value="">— Seleccionar —</option>
                                @foreach($maritalOptions as $op)
                                    <option value="{{ $op }}" @selected(old('guarantor_marital_status') === $op)>{{ $op }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-full" style="margin-top:.5rem;">
                        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Registrar cliente
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ══════ TABLA ══════ --}}
    <div class="col-12 col-xl-8">
        <div class="card" style="overflow:hidden;">

            {{-- Search --}}
            <div class="search-bar">
                <div class="input-wrap" style="flex:1;min-width:220px;">
                    <span class="input-icon">
                        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/>
                        </svg>
                    </span>
                    <input class="field-input" id="clientSearch"
                           type="text" placeholder="Buscar por nombre o número...">
                </div>
                <span style="font-size:.84rem;color:var(--muted);">{{ count($clients) }} cliente(s)</span>
            </div>

            {{-- Tabla --}}
            <div class="card-body-flush">
                <div class="table-wrap">
                    <table class="tbl" id="clientsTable">
                        <thead>
                            <tr>
                                <th>N° Cliente</th>
                                <th>Nombre</th>
                                <th>Teléfono</th>
                                <th>Cobrador</th>
                                <th>Estado préstamo</th>
                                <th>Próximo pago</th>
                                <th style="min-width:180px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($clients as $c)
                                @php
                                    $cid     = $c['id'] ?? null;
                                    $assigned= $c['assigned_username'] ?? null;
                                    $loanSt  = $c['loan_status'] ?? null;
                                    $overdue = (int)($c['overdue_count'] ?? 0);
                                    $nextDue = $c['next_due_date'] ?? null;

                                    if($loanSt === 'AL_CORRIENTE'){
                                        $rowCls = 'row-paid'; $bCls = 'badge-teal'; $bLbl = 'Al corriente';
                                    } elseif($loanSt === 'ATRASADO'){
                                        $rowCls = 'row-late'; $bCls = 'badge-red'; $bLbl = 'Atrasado ('.$overdue.')';
                                    } else {
                                        $rowCls = ''; $bCls = 'badge-gray'; $bLbl = 'Sin préstamo';
                                    }
                                @endphp
                                <tr class="{{ $rowCls }}"
                                    data-name="{{ strtolower(($c['full_name']??'').' '.($c['client_number']??'')) }}">
                                    <td>
                                        <span class="client-num">{{ $c['client_number'] ?? '—' }}</span>
                                    </td>
                                    <td>
                                        <div class="client-name">{{ $c['full_name'] ?? '—' }}</div>
                                        @if(!empty($c['occupation']))
                                            <div class="client-sub">{{ $c['occupation'] }}</div>
                                        @endif
                                    </td>
                                    <td class="mono cell-muted">{{ $c['phone'] ?? '—' }}</td>
                                    <td>
                                        @if($assigned)
                                            <span class="badge badge-blue">{{ $assigned }}</span>
                                        @else
                                            <span class="cell-muted" style="font-size:.85rem;">Sin asignar</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $bCls }}">{{ $bLbl }}</span>
                                    </td>
                                    <td class="mono cell-muted" style="font-size:.82rem;">
                                        {{ $nextDue ?? '—' }}
                                    </td>
                                    <td>
                                        @if($cid)
                                        <div style="display:flex;flex-direction:column;gap:.4rem;">
                                            {{-- Asignar --}}
                                            <form method="POST" action="{{ route('clients.assign', ['clientId'=>$cid]) }}">
                                                @csrf
                                                <div class="assign-inline">
                                                    <select name="user_id" required>
                                                        <option value="">Cobrador...</option>
                                                        @foreach($collectors as $u)
                                                            <option value="{{ $u['id'] }}">{{ $u['username'] }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button class="btn btn-ghost btn-sm" type="submit">Asignar</button>
                                                </div>
                                            </form>
                                            {{-- Nuevo préstamo --}}
                                            <a href="{{ route('loans.create', ['client_id'=>$cid]) }}"
                                               class="btn btn-primary btn-sm" style="width:fit-content;">
                                                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" d="M12 5v14M5 12h14"/>
                                                </svg>
                                                Préstamo
                                            </a>
                                        </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" style="text-align:center;padding:3rem;color:var(--muted);">
                                        <svg width="36" height="36" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="display:block;margin:0 auto .75rem;opacity:.35;">
                                            <path stroke-linecap="round" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8z"/>
                                        </svg>
                                        No hay clientes registrados aún.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Solo dígitos
document.querySelectorAll('.digits-only').forEach(function(el){
    el.addEventListener('input', function(){ this.value = this.value.replace(/\D/g,'').slice(0,10); });
});
// Búsqueda
var si = document.getElementById('clientSearch');
var rows = document.querySelectorAll('#clientsTable tbody tr[data-name]');
if(si){
    si.addEventListener('input', function(){
        var q = this.value.toLowerCase().trim();
        rows.forEach(function(row){
            row.style.display = (!q || row.getAttribute('data-name').includes(q)) ? '' : 'none';
        });
    });
}
</script>
@endpush