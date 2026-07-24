@extends('layouts.app')
@section('title', 'Clientes — MYBANK')

@push('styles')
<style>
/* ── Estilos específicos de la página Clientes ── */
.client-num  { font-weight: 800; font-family: 'Courier New', monospace; color: var(--blue); font-size: .88rem; white-space: nowrap; }
.client-name { font-weight: 700; white-space: nowrap; }
.client-sub  { font-size: .80rem; color: var(--muted); margin-top: .1rem; }

/* Secciones dentro de Drawers */
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
        <p class="page-sub">Gestiona la información de tus clientes y asigna cobradores de manera eficiente.</p>
    </div>
    <div>
        <button class="btn btn-primary" onclick="openDrawer('registerClientDrawer')">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" d="M12 5v14M5 12h14"/>
            </svg>
            Registrar Cliente
        </button>
    </div>
</div>

<div class="row g-3">
    <div class="col-12">
        {{-- Advanced Filter and Search Bar --}}
        <div class="card mb-3" style="overflow:visible;">
            <div class="search-bar" style="border-bottom: none;">
                <div class="input-wrap" style="flex:1;min-width:220px;">
                    <span class="input-icon">
                        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/>
                        </svg>
                    </span>
                    <input class="field-input" id="clientSearch" type="text" placeholder="Buscar por nombre o número..." oninput="filterClients()">
                </div>
                
                <button type="button" class="filter-toggle-btn" id="filterToggleBtn" onclick="toggleFilters()">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    Filtros avanzados
                </button>
                <span id="clientsCountText" style="font-size:.84rem;color:var(--muted); font-weight: 600;">{{ count($clients) }} cliente(s)</span>
            </div>

            {{-- Collapsible filters --}}
            <div class="collapsible-filters" id="collapsibleFilters">
                <div class="grid-3">
                    <div class="field">
                        <label class="field-label">Estado Préstamo</label>
                        <select class="field-input field-select" id="filterLoanStatus" onchange="filterClients()">
                            <option value="">Todos</option>
                            <option value="AL_CORRIENTE">Al corriente</option>
                            <option value="ATRASADO">Atrasado</option>
                            <option value="SIN_PRESTAMO">Sin préstamo</option>
                        </select>
                    </div>
                    <div class="field">
                        <label class="field-label">Cobrador Asignado</label>
                        <select class="field-input field-select" id="filterCollector" onchange="filterClients()">
                            <option value="">Todos</option>
                            @foreach($collectors as $u)
                                <option value="{{ $u['id'] }}">{{ $u['username'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label class="field-label">Estado Civil</label>
                        <select class="field-input field-select" id="filterMarital" onchange="filterClients()">
                            <option value="">Todos</option>
                            @foreach($maritalOptions as $op)
                                <option value="{{ $op }}">{{ $op }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Table Card --}}
        <div class="card" style="overflow:hidden;">
            <div class="card-body-flush">
                <div class="table-wrap">
                    <table class="tbl" id="clientsTable">
                        <thead>
                            <tr>
                                <th>N° Cliente</th>
                                <th>Nombre / Ocupación</th>
                                <th>Teléfono</th>
                                <th>Cobrador Asignado</th>
                                <th>Estado préstamo</th>
                                <th>Próximo pago</th>
                                <th style="width: 50px; text-align: center;">Acciones</th>
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
                                        $loanDataSt = 'AL_CORRIENTE';
                                    } elseif($loanSt === 'ATRASADO'){
                                        $rowCls = 'row-late'; $bCls = 'badge-red'; $bLbl = 'Atrasado ('.$overdue.')';
                                        $loanDataSt = 'ATRASADO';
                                    } else {
                                        $rowCls = ''; $bCls = 'badge-gray'; $bLbl = 'Sin préstamo';
                                        $loanDataSt = 'SIN_PRESTAMO';
                                    }
                                @endphp
                                <tr class="{{ $rowCls }}"
                                    data-name="{{ strtolower(($c['full_name']??'').' '.($c['client_number']??'')) }}"
                                    data-loan-status="{{ $loanDataSt }}"
                                    data-collector-id="{{ $c['assigned_user_id'] ?? '' }}"
                                    data-marital-status="{{ $c['marital_status'] ?? '' }}">
                                    <td>
                                        <span class="client-num">{{ $c['client_number'] ?? '—' }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('clients.show', ['clientId'=>$cid]) }}" style="text-decoration:none;">
                                            <div class="client-name" style="color:var(--blue);">{{ $c['full_name'] ?? '—' }}</div>
                                        </a>
                                        @if(!empty($c['occupation']))
                                            <div class="client-sub">{{ $c['occupation'] }} • ${{ number_format($c['monthly_income'] ?? 0, 2) }}</div>
                                        @endif
                                    </td>
                                    <td class="mono cell-muted">{{ $c['phone'] ?? '—' }}</td>
                                    <td>
                                        @if($cid)
                                            <form method="POST" action="{{ route('clients.assign', ['clientId'=>$cid]) }}" style="margin:0;">
                                                @csrf
                                                <div class="assign-inline">
                                                    <select name="user_id" onchange="this.form.submit()" required>
                                                        <option value="">— Sin asignar —</option>
                                                        @foreach($collectors as $u)
                                                            <option value="{{ $u['id'] }}" @selected(($c['assigned_user_id']??null)==$u['id'])>
                                                                {{ $u['username'] }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </form>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $bCls }}">
                                            <span class="badge-dot"></span>
                                            {{ $bLbl }}
                                        </span>
                                    </td>
                                    <td class="mono cell-muted" style="font-size:.82rem;">
                                        {{ $nextDue ?? '—' }}
                                    </td>
                                    <td style="text-align: center;">
                                        @if($cid)
                                            <div class="meatball-menu">
                                                <button class="meatball-btn" type="button" aria-label="Acciones">
                                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                                                    </svg>
                                                </button>
                                                <div class="meatball-dropdown">
                                                    <a href="{{ route('clients.show', ['clientId'=>$cid]) }}" class="meatball-item">
                                                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                        </svg>
                                                        Ver Perfil
                                                    </a>
                                                    <a href="{{ route('loans.create', ['client_id'=>$cid]) }}" class="meatball-item">
                                                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" d="M12 5v14M5 12h14"/>
                                                        </svg>
                                                        Nuevo Préstamo
                                                    </a>
                                                    <button type="button" class="meatball-item" onclick='openEdit({{ $cid }}, @json($c))'>
                                                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                        </svg>
                                                        Editar Cliente
                                                    </button>
                                                    @if($loanSt !== null && $loanSt !== 'SIN_PRESTAMO')
                                                        <button type="button" class="meatball-item text-danger" onclick="showToast('No se puede eliminar un cliente con préstamos activos.', 'danger')">
                                                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <polyline points="3 6 5 6 21 6"/><path stroke-linecap="round" d="M19 6l-1 14H6L5 6M10 11v6M14 11v6M9 6V4h6v2"/>
                                                            </svg>
                                                            Borrar Cliente
                                                        </button>
                                                    @else
                                                        <form method="POST" action="{{ route('clients.destroy', ['clientId' => $cid]) }}" style="display:inline;">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="meatball-item text-danger btn-confirm" data-confirm-text="¿Confirmar borrar?">
                                                                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                    <polyline points="3 6 5 6 21 6"/><path stroke-linecap="round" d="M19 6l-1 14H6L5 6M10 11v6M14 11v6M9 6V4h6v2"/>
                                                                </svg>
                                                                Borrar Cliente
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
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

{{-- ── DRAWER: REGISTRAR CLIENTE ── --}}
<div class="drawer" id="registerClientDrawer">
    <div class="drawer-head">
        <h3 class="drawer-title">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" d="M12 5v14M5 12h14"/>
            </svg>
            Registrar nuevo cliente
        </h3>
        <button class="drawer-close" onclick="closeDrawer('registerClientDrawer')">✕</button>
    </div>
    <form method="POST" action="{{ route('clients.store') }}" autocomplete="off">
        @csrf
        <div class="drawer-body">
            <div class="form-section">
                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="8" r="4"/><path stroke-linecap="round" d="M4 20v-1a5 5 0 015-5h6a5 5 0 015 5v1"/>
                </svg>
                Datos personales
            </div>

            <div class="field">
                <label class="field-label field-required">Nombre completo</label>
                <input class="field-input" name="full_name" value="{{ old('full_name') }}" maxlength="150" required>
            </div>

            <div class="grid-2">
                <div class="field">
                    <label class="field-label field-required">Teléfono</label>
                    <input class="field-input digits-only" name="phone" value="{{ old('phone') }}"
                           maxlength="10" inputmode="numeric" required>
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
                <input class="field-input" name="address" value="{{ old('address') }}" maxlength="255" required>
            </div>

            <div class="field">
                <label class="field-label">Nombre del cónyuge</label>
                <input class="field-input" name="spouse_full_name" value="{{ old('spouse_full_name') }}" maxlength="150">
            </div>

            <div class="grid-3">
                <div class="field">
                    <label class="field-label">Fecha nacimiento</label>
                    <input class="field-input" type="date" name="birth_date" value="{{ old('birth_date') }}" max="{{ date('Y-m-d', strtotime('-18 years')) }}">
                </div>
                <div class="field">
                    <label class="field-label">Ocupación</label>
                    <input class="field-input" name="occupation" value="{{ old('occupation') }}" maxlength="100">
                </div>
                <div class="field">
                    <label class="field-label">Ingreso mensual</label>
                    <input class="field-input" type="number" name="monthly_income" value="{{ old('monthly_income') }}" min="0" step="100">
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
                <input class="field-input" name="guarantor_full_name" value="{{ old('guarantor_full_name') }}" maxlength="150" required>
            </div>

            <div class="field">
                <label class="field-label field-required">Dirección del aval</label>
                <input class="field-input" name="guarantor_address" value="{{ old('guarantor_address') }}" maxlength="255" required>
            </div>

            <div class="grid-2">
                <div class="field">
                    <label class="field-label field-required">Teléfono del aval</label>
                    <input class="field-input digits-only" name="guarantor_phone" value="{{ old('guarantor_phone') }}"
                           maxlength="10" inputmode="numeric" required>
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
        </div>
        <div class="drawer-foot">
            <button type="button" class="btn btn-ghost" onclick="closeDrawer('registerClientDrawer')">Cancelar</button>
            <button type="submit" class="btn btn-primary">Registrar cliente</button>
        </div>
    </form>
</div>

{{-- ── DRAWER: EDITAR CLIENTE ── --}}
<div class="drawer" id="editClientDrawer">
    <div class="drawer-head">
        <h3 class="drawer-title">✏️ Editar cliente</h3>
        <button class="drawer-close" onclick="closeDrawer('editClientDrawer')">✕</button>
    </div>
    <form method="POST" id="editForm" action="" autocomplete="off">
        @csrf @method('PATCH')
        <div class="drawer-body">
            <div class="form-section">
                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="8" r="4"/><path stroke-linecap="round" d="M4 20v-1a5 5 0 015-5h6a5 5 0 015 5v1"/>
                </svg>
                Datos personales
            </div>

            <div class="field">
                <label class="field-label field-required">Nombre completo</label>
                <input class="field-input" id="e_full_name" name="full_name" maxlength="150" required>
            </div>

            <div class="grid-2">
                <div class="field">
                    <label class="field-label field-required">Teléfono</label>
                    <input class="field-input digits-only" id="e_phone" name="phone" maxlength="10" required>
                </div>
                <div class="field">
                    <label class="field-label field-required">Estado civil</label>
                    <select class="field-input field-select" id="e_marital" name="marital_status" required>
                        @foreach($maritalOptions as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="field">
                <label class="field-label field-required">Dirección</label>
                <input class="field-input" id="e_address" name="address" maxlength="255" required>
            </div>

            <div class="grid-3">
                <div class="field">
                    <label class="field-label">Cónyuge</label>
                    <input class="field-input" id="e_spouse" name="spouse_full_name" maxlength="150">
                </div>
                <div class="field">
                    <label class="field-label">Ocupación</label>
                    <input class="field-input" id="e_occupation" name="occupation" maxlength="100">
                </div>
                <div class="field">
                    <label class="field-label">Fecha nacimiento</label>
                    <input class="field-input" type="date" id="e_birth" name="birth_date">
                </div>
            </div>

            <div class="field">
                <label class="field-label">Ingreso mensual</label>
                <input class="field-input" type="number" step="0.01" id="e_income" name="monthly_income" min="0">
            </div>

            <div class="form-section">
                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                Datos del AVAL
            </div>

            <div class="field">
                <label class="field-label field-required">Nombre completo del aval</label>
                <input class="field-input" id="e_gname" name="guarantor_full_name" maxlength="150" required>
            </div>

            <div class="field">
                <label class="field-label field-required">Dirección del aval</label>
                <input class="field-input" id="e_gaddress" name="guarantor_address" maxlength="255" required>
            </div>

            <div class="grid-2">
                <div class="field">
                    <label class="field-label field-required">Teléfono del aval</label>
                    <input class="field-input digits-only" id="e_gphone" name="guarantor_phone" maxlength="10" required>
                </div>
                <div class="field">
                    <label class="field-label field-required">Estado civil del aval</label>
                    <select class="field-input field-select" id="e_gmarital" name="guarantor_marital_status" required>
                        @foreach($maritalOptions as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="drawer-foot">
            <button type="button" class="btn btn-ghost" onclick="closeDrawer('editClientDrawer')">Cancelar</button>
            <button type="submit" class="btn btn-primary">💾 Guardar cambios</button>
        </div>
    </form>
</div>

@endsection

@push('scripts')
<script>
function openEdit(id, data){
    document.getElementById('editForm').action = '/clients/' + id;
    document.getElementById('e_full_name').value  = data.full_name   || '';
    document.getElementById('e_phone').value      = data.phone       || '';
    document.getElementById('e_address').value    = data.address     || '';
    document.getElementById('e_spouse').value     = data.spouse_full_name || '';
    document.getElementById('e_occupation').value = data.occupation  || '';
    document.getElementById('e_birth').value      = data.birth_date  || '';
    document.getElementById('e_income').value     = data.monthly_income != null ? data.monthly_income : '';
    
    var ms = document.getElementById('e_marital');
    for(var i=0; i<ms.options.length; i++) {
        ms.options[i].selected = ms.options[i].value === (data.marital_status || '');
    }
    
    var g = data.guarantor || {};
    document.getElementById('e_gname').value    = g.full_name    || '';
    document.getElementById('e_gphone').value   = g.phone        || '';
    document.getElementById('e_gaddress').value = g.address      || '';
    
    var gm = document.getElementById('e_gmarital');
    for(var i=0; i<gm.options.length; i++) {
        gm.options[i].selected = gm.options[i].value === (g.marital_status || '');
    }
    
    openDrawer('editClientDrawer');
}

// Digits-only phone restriction
document.querySelectorAll('.digits-only').forEach(function(el){
    el.addEventListener('input', function(){ this.value = this.value.replace(/\D/g,'').slice(0,10); });
});

// Collapsible advanced filters
function toggleFilters() {
    const filters = document.getElementById('collapsibleFilters');
    const btn = document.getElementById('filterToggleBtn');
    if (filters && btn) {
        filters.classList.toggle('show');
        btn.classList.toggle('active');
    }
}

// Global filter client-side logic
function filterClients() {
    const q = document.getElementById('clientSearch').value.toLowerCase().trim();
    const loanSt = document.getElementById('filterLoanStatus').value;
    const collector = document.getElementById('filterCollector').value;
    const marital = document.getElementById('filterMarital').value;
    
    const rows = document.querySelectorAll('#clientsTable tbody tr[data-name]');
    let count = 0;
    
    rows.forEach(function(row) {
        const nameMatch = !q || row.getAttribute('data-name').includes(q);
        const loanMatch = !loanSt || row.getAttribute('data-loan-status') === loanSt;
        const collectorMatch = !collector || row.getAttribute('data-collector-id') === collector;
        const maritalMatch = !marital || row.getAttribute('data-marital-status') === marital;
        
        if (nameMatch && loanMatch && collectorMatch && maritalMatch) {
            row.style.display = '';
            count++;
        } else {
            row.style.display = 'none';
        }
    });
    
    const countDisplay = document.getElementById('clientsCountText');
    if (countDisplay) {
        countDisplay.textContent = `${count} cliente(s)`;
    }
}
</script>
@endpush