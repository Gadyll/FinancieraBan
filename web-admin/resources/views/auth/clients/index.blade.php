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

/* ── Filtros en Encabezados de Columna ── */
.th-filterable {
    position: relative;
    cursor: pointer;
    user-select: none;
    transition: background-color .15s ease;
}
.th-filterable:hover {
    background-color: rgba(26, 111, 207, 0.05);
}
.th-filter-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .4rem;
}
.th-filter-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    border-radius: 6px;
    border: 1px solid rgba(26, 111, 207, 0.20);
    background: #ffffff;
    color: var(--blue);
    cursor: pointer;
    transition: all .15s ease;
    flex-shrink: 0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.th-filterable:hover .th-filter-btn,
.th-filterable.has-filter .th-filter-btn {
    background: var(--blue);
    border-color: var(--blue);
    color: #ffffff;
    box-shadow: 0 2px 6px rgba(26, 111, 207, 0.35);
}
.th-filter-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--blue);
    display: none;
    box-shadow: 0 0 0 2px #ffffff;
}
.th-filterable.has-filter .th-filter-dot {
    display: inline-block;
}

/* Recuadros / Popovers Flotantes de Búsqueda */
.col-filter-popover {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    min-width: 260px;
    background: #ffffff;
    border: 1.5px solid rgba(26, 111, 207, 0.22);
    border-radius: 12px;
    box-shadow: 0 12px 32px rgba(13, 27, 46, 0.18);
    z-index: 1050;
    padding: .85rem;
    display: none;
    text-transform: none;
    letter-spacing: normal;
    font-weight: normal;
    font-size: .88rem;
    animation: colPopoverAnim 0.18s cubic-bezier(0.16, 1, 0.3, 1) both;
}
.col-filter-popover.popover-right {
    left: auto;
    right: 0;
}
@keyframes colPopoverAnim {
    from { opacity: 0; transform: translateY(-4px) scale(0.97); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
.col-filter-popover.show {
    display: block;
}
.popover-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: .6rem;
    padding-bottom: .4rem;
    border-bottom: 1px solid rgba(26, 111, 207, 0.10);
    font-size: .78rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--blue);
}
.popover-close-btn {
    background: transparent;
    border: none;
    color: var(--muted);
    font-size: 1.25rem;
    line-height: 1;
    cursor: pointer;
    padding: 0 .2rem;
    border-radius: 4px;
}
.popover-close-btn:hover {
    color: var(--red);
}
.popover-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: .75rem;
    padding-top: .5rem;
    border-top: 1px solid rgba(26, 111, 207, 0.08);
}
.field-input-sm {
    padding: .45rem .75rem !important;
    font-size: .85rem !important;
    height: auto !important;
}

/* Grupo de botones para estado */
.status-pill-group {
    display: flex;
    flex-direction: column;
    gap: .35rem;
}
.status-pill-btn {
    display: flex;
    align-items: center;
    gap: .5rem;
    padding: .45rem .75rem;
    border-radius: 8px;
    border: 1px solid rgba(26, 111, 207, 0.12);
    background: #f8fafc;
    font-size: .82rem;
    font-weight: 600;
    color: var(--text-2);
    cursor: pointer;
    transition: all .15s ease;
    text-align: left;
    width: 100%;
}
.status-pill-btn:hover {
    background: rgba(26, 111, 207, 0.06);
    border-color: var(--blue);
    color: var(--blue);
}
.status-pill-btn.active {
    background: var(--blue);
    border-color: var(--blue);
    color: #ffffff;
}
.status-pill-btn.active .badge-dot {
    background-color: #ffffff !important;
}

/* Barra de filtros activos */
.active-filters-bar {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: .5rem;
    padding: .5rem .85rem;
    background: rgba(26, 111, 207, 0.05);
    border: 1px dashed rgba(26, 111, 207, 0.22);
    border-radius: 10px;
    margin-bottom: .85rem;
}
.active-filters-label {
    font-size: .76rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--blue);
    margin-right: .25rem;
}
.filter-chip {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    background: #ffffff;
    border: 1px solid rgba(26, 111, 207, 0.25);
    padding: .25rem .6rem;
    border-radius: 999px;
    font-size: .78rem;
    font-weight: 600;
    color: var(--text);
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.filter-chip-remove {
    background: none;
    border: none;
    color: var(--muted);
    font-size: .95rem;
    cursor: pointer;
    line-height: 1;
    display: flex;
    align-items: center;
}
.filter-chip-remove:hover {
    color: var(--red);
}
.btn-clear-all-chips {
    background: none;
    border: none;
    color: var(--red);
    font-size: .78rem;
    font-weight: 700;
    cursor: pointer;
    text-decoration: underline;
    margin-left: auto;
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
                    @php
                        $me = session('mybank_user');
                        $myId = $me['id'] ?? null;
                        $myRole = $me['role'] ?? 'USER';
                    @endphp
                    <div class="field">
                        <label class="field-label">Cobrador Asignado</label>
                        <select class="field-input field-select" id="filterCollector" onchange="filterClients()">
                            <option value="">Todos los clientes</option>
                            @if($myRole === 'USER' && $myId)
                                <option value="{{ $myId }}" style="font-weight: bold;">Mis clientes</option>
                                <optgroup label="Otros cobradores">
                                @foreach($collectors as $u)
                                    @if($u['id'] != $myId)
                                        <option value="{{ $u['id'] }}">{{ $u['username'] }}</option>
                                    @endif
                                @endforeach
                                </optgroup>
                            @else
                                @foreach($collectors as $u)
                                    <option value="{{ $u['id'] }}">{{ $u['username'] }}</option>
                                @endforeach
                            @endif
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
        <div class="card" style="overflow:visible;">
            {{-- Active Filter Chips Bar --}}
            <div class="active-filters-bar" id="activeFiltersBar" style="display:none; margin: 1rem 1.25rem .5rem;">
                <span class="active-filters-label">Filtros aplicados:</span>
                <div class="active-filters-chips" id="activeFiltersChips" style="display:flex;gap:.4rem;flex-wrap:wrap;"></div>
                <button type="button" class="btn-clear-all-chips" onclick="clearAllFilters()">
                    Limpiar todos los filtros
                </button>
            </div>

            <div class="card-body-flush">
                <div class="table-wrap" style="overflow-x:auto; overflow-y:visible; min-height: 380px;">
                    <table class="tbl" id="clientsTable">
                        <thead>
                            <tr>
                                {{-- N° Cliente --}}
                                <th class="th-filterable" id="th_number" onclick="openColPopover('number', event)">
                                    <div class="th-filter-header">
                                        <span>N° Cliente</span>
                                        <div style="display:flex;align-items:center;gap:4px;">
                                            <span class="th-filter-dot" id="dot_number"></span>
                                            <button type="button" class="th-filter-btn" title="Buscar por N° de cliente">
                                                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-filter-popover" id="popover_number" onclick="event.stopPropagation()">
                                        <div class="popover-head">
                                            <span>Buscar N° Cliente</span>
                                            <button type="button" class="popover-close-btn" onclick="closeAllPopovers()">&times;</button>
                                        </div>
                                        <div class="input-wrap">
                                            <span class="input-icon">
                                                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
                                            </span>
                                            <input type="text" class="field-input field-input-sm" id="col_filter_number" placeholder="Ej. Cliente00001..." oninput="filterClients()">
                                        </div>
                                        <div class="popover-foot">
                                            <button type="button" class="btn btn-ghost btn-sm" onclick="clearColFilter('number')">Limpiar</button>
                                            <button type="button" class="btn btn-primary btn-sm" onclick="closeAllPopovers()">Aceptar</button>
                                        </div>
                                    </div>
                                </th>

                                {{-- Nombre / Ocupación --}}
                                <th class="th-filterable" id="th_name" onclick="openColPopover('name', event)">
                                    <div class="th-filter-header">
                                        <span>Nombre / Ocupación</span>
                                        <div style="display:flex;align-items:center;gap:4px;">
                                            <span class="th-filter-dot" id="dot_name"></span>
                                            <button type="button" class="th-filter-btn" title="Buscar por nombre u ocupación">
                                                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-filter-popover" id="popover_name" onclick="event.stopPropagation()">
                                        <div class="popover-head">
                                            <span>Buscar Nombre u Ocupación</span>
                                            <button type="button" class="popover-close-btn" onclick="closeAllPopovers()">&times;</button>
                                        </div>
                                        <div class="input-wrap">
                                            <span class="input-icon">
                                                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
                                            </span>
                                            <input type="text" class="field-input field-input-sm" id="col_filter_name" placeholder="Ej. Cruz, Manuel, Comerciante..." oninput="filterClients()">
                                        </div>
                                        <div class="popover-foot">
                                            <button type="button" class="btn btn-ghost btn-sm" onclick="clearColFilter('name')">Limpiar</button>
                                            <button type="button" class="btn btn-primary btn-sm" onclick="closeAllPopovers()">Aceptar</button>
                                        </div>
                                    </div>
                                </th>

                                {{-- Teléfono --}}
                                <th class="th-filterable" id="th_phone" onclick="openColPopover('phone', event)">
                                    <div class="th-filter-header">
                                        <span>Teléfono</span>
                                        <div style="display:flex;align-items:center;gap:4px;">
                                            <span class="th-filter-dot" id="dot_phone"></span>
                                            <button type="button" class="th-filter-btn" title="Buscar por teléfono">
                                                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-filter-popover" id="popover_phone" onclick="event.stopPropagation()">
                                        <div class="popover-head">
                                            <span>Buscar por Teléfono</span>
                                            <button type="button" class="popover-close-btn" onclick="closeAllPopovers()">&times;</button>
                                        </div>
                                        <div class="input-wrap">
                                            <span class="input-icon">
                                                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                            </span>
                                            <input type="text" class="field-input field-input-sm digits-only" id="col_filter_phone" placeholder="Ej. 71229..." oninput="filterClients()">
                                        </div>
                                        <div class="popover-foot">
                                            <button type="button" class="btn btn-ghost btn-sm" onclick="clearColFilter('phone')">Limpiar</button>
                                            <button type="button" class="btn btn-primary btn-sm" onclick="closeAllPopovers()">Aceptar</button>
                                        </div>
                                    </div>
                                </th>

                                {{-- Cobrador Asignado --}}
                                <th class="th-filterable" id="th_collector" onclick="openColPopover('collector', event)">
                                    <div class="th-filter-header">
                                        <span>Cobrador Asignado</span>
                                        <div style="display:flex;align-items:center;gap:4px;">
                                            <span class="th-filter-dot" id="dot_collector"></span>
                                            <button type="button" class="th-filter-btn" title="Filtrar por cobrador">
                                                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-filter-popover" id="popover_collector" onclick="event.stopPropagation()">
                                        <div class="popover-head">
                                            <span>Filtrar por Cobrador</span>
                                            <button type="button" class="popover-close-btn" onclick="closeAllPopovers()">&times;</button>
                                        </div>
                                        <div class="field" style="margin-bottom:0;">
                                            <select class="field-input field-select field-input-sm" id="col_filter_collector" onchange="syncCollectorFilter(this.value)">
                                                <option value="">Todos los cobradores</option>
                                                @if($myRole === 'USER' && $myId)
                                                    <option value="{{ $myId }}" style="font-weight: bold;">⭐ Mis clientes</option>
                                                    <option value="UNASSIGNED">⚪ Sin asignar</option>
                                                    <optgroup label="Otros cobradores">
                                                    @foreach($collectors as $u)
                                                        @if($u['id'] != $myId)
                                                            <option value="{{ $u['id'] }}">{{ $u['username'] }}</option>
                                                        @endif
                                                    @endforeach
                                                    </optgroup>
                                                @else
                                                    <option value="UNASSIGNED">⚪ Sin asignar</option>
                                                    @foreach($collectors as $u)
                                                        <option value="{{ $u['id'] }}">{{ $u['username'] }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>
                                        <div class="popover-foot">
                                            <button type="button" class="btn btn-ghost btn-sm" onclick="clearColFilter('collector')">Limpiar</button>
                                            <button type="button" class="btn btn-primary btn-sm" onclick="closeAllPopovers()">Aceptar</button>
                                        </div>
                                    </div>
                                </th>

                                {{-- Estado préstamo --}}
                                <th class="th-filterable" id="th_loan_status" onclick="openColPopover('loan_status', event)">
                                    <div class="th-filter-header">
                                        <span>Estado préstamo</span>
                                        <div style="display:flex;align-items:center;gap:4px;">
                                            <span class="th-filter-dot" id="dot_loan_status"></span>
                                            <button type="button" class="th-filter-btn" title="Filtrar por estado del préstamo">
                                                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 6v6l4 2"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-filter-popover popover-right" id="popover_loan_status" onclick="event.stopPropagation()">
                                        <div class="popover-head">
                                            <span>Estado del Préstamo</span>
                                            <button type="button" class="popover-close-btn" onclick="closeAllPopovers()">&times;</button>
                                        </div>
                                        <div class="status-pill-group">
                                            <button type="button" class="status-pill-btn active" data-status="" onclick="setColLoanStatus('')">
                                                <span>📋</span> Todos los estados
                                            </button>
                                            <button type="button" class="status-pill-btn" data-status="AL_CORRIENTE" onclick="setColLoanStatus('AL_CORRIENTE')">
                                                <span class="badge-dot" style="background:#0db88a;"></span> Al corriente
                                            </button>
                                            <button type="button" class="status-pill-btn" data-status="ATRASADO" onclick="setColLoanStatus('ATRASADO')">
                                                <span class="badge-dot" style="background:#e03a3a;"></span> Atrasado
                                            </button>
                                            <button type="button" class="status-pill-btn" data-status="SIN_PRESTAMO" onclick="setColLoanStatus('SIN_PRESTAMO')">
                                                <span class="badge-dot" style="background:#8896a8;"></span> Sin préstamo
                                            </button>
                                        </div>
                                        <div class="popover-foot">
                                            <button type="button" class="btn btn-ghost btn-sm" onclick="clearColFilter('loan_status')">Limpiar</button>
                                            <button type="button" class="btn btn-primary btn-sm" onclick="closeAllPopovers()">Aceptar</button>
                                        </div>
                                    </div>
                                </th>

                                {{-- Próximo pago --}}
                                <th class="th-filterable" id="th_next_due" onclick="openColPopover('next_due', event)">
                                    <div class="th-filter-header">
                                        <span>Próximo pago</span>
                                        <div style="display:flex;align-items:center;gap:4px;">
                                            <span class="th-filter-dot" id="dot_next_due"></span>
                                            <button type="button" class="th-filter-btn" title="Buscar por fecha de próximo pago">
                                                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-filter-popover popover-right" id="popover_next_due" onclick="event.stopPropagation()">
                                        <div class="popover-head">
                                            <span>Buscar Próximo Pago</span>
                                            <button type="button" class="popover-close-btn" onclick="closeAllPopovers()">&times;</button>
                                        </div>
                                        <div class="field" style="margin-bottom:.5rem;">
                                            <label class="field-label" style="font-size:.74rem;">Por fecha exacta:</label>
                                            <input type="date" class="field-input field-input-sm" id="col_filter_next_due" onchange="filterClients()">
                                        </div>
                                        <div class="field" style="margin-bottom:0;">
                                            <label class="field-label" style="font-size:.74rem;">O texto (ej. 2026-07 o 13):</label>
                                            <input type="text" class="field-input field-input-sm" id="col_filter_next_due_text" placeholder="Año, mes o día..." oninput="filterClients()">
                                        </div>
                                        <div class="popover-foot">
                                            <button type="button" class="btn btn-ghost btn-sm" onclick="clearColFilter('next_due')">Limpiar</button>
                                            <button type="button" class="btn btn-primary btn-sm" onclick="closeAllPopovers()">Aceptar</button>
                                        </div>
                                    </div>
                                </th>

                                <th style="width: 50px; text-align: center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Fila cuando no hay resultados de búsqueda --}}
                            <tr id="noResultsRow" style="display:none;">
                                <td colspan="7" style="text-align: center; padding: 3rem 1.5rem;">
                                    <div style="max-width: 360px; margin: 0 auto; color: var(--muted);">
                                        <svg width="40" height="40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto .75rem; opacity: .45; display:block;">
                                            <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/>
                                        </svg>
                                        <div style="font-weight: 700; color: var(--text); font-size: 1rem; margin-bottom: .35rem;">No se encontraron clientes</div>
                                        <p style="font-size: .85rem; margin-bottom: 1rem;">Ningún cliente coincide con los criterios de búsqueda o filtros seleccionados.</p>
                                        <button type="button" class="btn btn-ghost btn-sm" onclick="clearAllFilters()">
                                            Restablecer todos los filtros
                                        </button>
                                    </div>
                                </td>
                            </tr>
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
                                    data-number="{{ strtolower($c['client_number'] ?? '') }}"
                                    data-name="{{ strtolower(($c['full_name']??'').' '.($c['occupation']??'')) }}"
                                    data-phone="{{ preg_replace('/\D/', '', $c['phone'] ?? '') }}"
                                    data-collector-id="{{ $c['assigned_user_id'] ?? '' }}"
                                    data-collector-name="{{ strtolower($c['assigned_username'] ?? '') }}"
                                    data-loan-status="{{ $loanDataSt }}"
                                    data-next-due="{{ $nextDue ?? '' }}"
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
                                            @if($myRole === 'ADMIN')
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
                                                <span class="cell-muted">{{ $c['assigned_username'] ?? '— Sin asignar —' }}</span>
                                            @endif
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
                                                    @if($myRole === 'ADMIN' || ($myRole === 'USER' && ($c['assigned_user_id'] ?? null) == $myId))
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

// ── Popovers flotantes de búsqueda por columna ──
function openColPopover(colKey, event) {
    if (event) {
        event.stopPropagation();
    }
    const popover = document.getElementById('popover_' + colKey);
    if (!popover) return;

    const wasOpen = popover.classList.contains('show');
    closeAllPopovers();

    if (!wasOpen) {
        popover.classList.add('show');
        const input = popover.querySelector('input, select');
        if (input) {
            setTimeout(function() {
                input.focus();
                if (input.select) input.select();
            }, 60);
        }
    }
}

function closeAllPopovers() {
    document.querySelectorAll('.col-filter-popover').forEach(function(p) {
        p.classList.remove('show');
    });
}

// Cerrar al hacer clic fuera
document.addEventListener('click', function(e) {
    if (!e.target.closest('.col-filter-popover') && !e.target.closest('.th-filterable')) {
        closeAllPopovers();
    }
});

// Cerrar con Escape o Buscar con Enter
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAllPopovers();
    } else if (e.key === 'Enter' && e.target.closest('.col-filter-popover')) {
        closeAllPopovers();
    }
});

function clearColFilter(colKey) {
    if (colKey === 'number') {
        const el = document.getElementById('col_filter_number');
        if (el) el.value = '';
    } else if (colKey === 'name') {
        const el = document.getElementById('col_filter_name');
        if (el) el.value = '';
    } else if (colKey === 'phone') {
        const el = document.getElementById('col_filter_phone');
        if (el) el.value = '';
    } else if (colKey === 'collector') {
        const el1 = document.getElementById('col_filter_collector');
        const el2 = document.getElementById('filterCollector');
        if (el1) el1.value = '';
        if (el2) el2.value = '';
    } else if (colKey === 'loan_status') {
        setColLoanStatus('');
    } else if (colKey === 'next_due') {
        const el1 = document.getElementById('col_filter_next_due');
        const el2 = document.getElementById('col_filter_next_due_text');
        if (el1) el1.value = '';
        if (el2) el2.value = '';
    }
    filterClients();
}

function setColLoanStatus(status) {
    const mainSel = document.getElementById('filterLoanStatus');
    if (mainSel) mainSel.value = status;
    document.querySelectorAll('.status-pill-btn').forEach(function(btn) {
        btn.classList.toggle('active', btn.getAttribute('data-status') === status);
    });
    filterClients();
}

function syncCollectorFilter(val) {
    const mainSel = document.getElementById('filterCollector');
    if (mainSel) mainSel.value = val;
    filterClients();
}

function clearAllFilters() {
    const qEl = document.getElementById('clientSearch');
    if (qEl) qEl.value = '';

    const numEl = document.getElementById('col_filter_number');
    if (numEl) numEl.value = '';

    const nameEl = document.getElementById('col_filter_name');
    if (nameEl) nameEl.value = '';

    const phoneEl = document.getElementById('col_filter_phone');
    if (phoneEl) phoneEl.value = '';

    const colSel1 = document.getElementById('col_filter_collector');
    if (colSel1) colSel1.value = '';

    const colSel2 = document.getElementById('filterCollector');
    if (colSel2) colSel2.value = '';

    const loanSel = document.getElementById('filterLoanStatus');
    if (loanSel) loanSel.value = '';

    const marSel = document.getElementById('filterMarital');
    if (marSel) marSel.value = '';

    const dueEl1 = document.getElementById('col_filter_next_due');
    if (dueEl1) dueEl1.value = '';

    const dueEl2 = document.getElementById('col_filter_next_due_text');
    if (dueEl2) dueEl2.value = '';

    document.querySelectorAll('.status-pill-btn').forEach(function(btn) {
        btn.classList.toggle('active', btn.getAttribute('data-status') === '');
    });

    closeAllPopovers();
    filterClients();
}

// ── Lógica Central de Filtrado ──
function filterClients() {
    const q = (document.getElementById('clientSearch')?.value || '').toLowerCase().trim();
    const colNumber = (document.getElementById('col_filter_number')?.value || '').toLowerCase().trim();
    const colName = (document.getElementById('col_filter_name')?.value || '').toLowerCase().trim();
    const colPhone = (document.getElementById('col_filter_phone')?.value || '').replace(/\D/g, '');
    const collector = document.getElementById('filterCollector')?.value || document.getElementById('col_filter_collector')?.value || '';
    const loanSt = document.getElementById('filterLoanStatus')?.value || '';
    const marital = document.getElementById('filterMarital')?.value || '';
    const nextDueDate = document.getElementById('col_filter_next_due')?.value || '';
    const nextDueText = (document.getElementById('col_filter_next_due_text')?.value || '').toLowerCase().trim();

    // Sincronizar selectores si cambiaron desde otro lugar
    const colSel = document.getElementById('col_filter_collector');
    if (colSel && colSel.value !== collector) {
        colSel.value = collector;
    }

    const rows = document.querySelectorAll('#clientsTable tbody tr[data-name]');
    const totalRows = rows.length;
    let visibleCount = 0;

    rows.forEach(function(row) {
        const rowNumber = row.getAttribute('data-number') || '';
        const rowName = row.getAttribute('data-name') || '';
        const rowPhone = row.getAttribute('data-phone') || '';
        const rowCollectorId = row.getAttribute('data-collector-id') || '';
        const rowCollectorName = row.getAttribute('data-collector-name') || '';
        const rowLoanStatus = row.getAttribute('data-loan-status') || '';
        const rowNextDue = row.getAttribute('data-next-due') || '';
        const rowMarital = row.getAttribute('data-marital-status') || '';

        // Búsqueda general
        const qMatch = !q || (rowNumber.includes(q) || rowName.includes(q) || rowPhone.includes(q) || rowCollectorName.includes(q));

        // Filtros específicos por columna
        const numberMatch = !colNumber || rowNumber.includes(colNumber);
        const nameMatch = !colName || rowName.includes(colName);
        const phoneMatch = !colPhone || rowPhone.includes(colPhone);

        let collectorMatch = true;
        if (collector === 'UNASSIGNED') {
            collectorMatch = !rowCollectorId;
        } else if (collector) {
            collectorMatch = (rowCollectorId === collector);
        }

        const loanMatch = !loanSt || (rowLoanStatus === loanSt);
        const maritalMatch = !marital || (rowMarital === marital);

        let dueMatch = true;
        if (nextDueDate) {
            dueMatch = (rowNextDue === nextDueDate);
        }
        if (dueMatch && nextDueText) {
            dueMatch = rowNextDue.includes(nextDueText);
        }

        if (qMatch && numberMatch && nameMatch && phoneMatch && collectorMatch && loanMatch && maritalMatch && dueMatch) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    // Fila de "No se encontraron clientes"
    const noResultsRow = document.getElementById('noResultsRow');
    if (noResultsRow) {
        noResultsRow.style.display = (visibleCount === 0 && totalRows > 0) ? '' : 'none';
    }

    // Actualizar indicador visual de filtros en cada columna (dot y clase .has-filter)
    function toggleColStatus(colKey, hasVal) {
        const th = document.getElementById('th_' + colKey);
        if (th) {
            th.classList.toggle('has-filter', hasVal);
        }
    }
    toggleColStatus('number', Boolean(colNumber));
    toggleColStatus('name', Boolean(colName));
    toggleColStatus('phone', Boolean(colPhone));
    toggleColStatus('collector', Boolean(collector));
    toggleColStatus('loan_status', Boolean(loanSt));
    toggleColStatus('next_due', Boolean(nextDueDate || nextDueText));

    // Renderizar Barra de Chips de Filtros Activos
    const activeBar = document.getElementById('activeFiltersBar');
    const chipsContainer = document.getElementById('activeFiltersChips');
    if (activeBar && chipsContainer) {
        chipsContainer.innerHTML = '';
        let activeFiltersList = [];

        if (q) {
            activeFiltersList.push({ label: `Búsqueda: "${q}"`, clear: () => { document.getElementById('clientSearch').value = ''; filterClients(); } });
        }
        if (colNumber) {
            activeFiltersList.push({ label: `N°: ${colNumber}`, clear: () => clearColFilter('number') });
        }
        if (colName) {
            activeFiltersList.push({ label: `Nombre: ${colName}`, clear: () => clearColFilter('name') });
        }
        if (colPhone) {
            activeFiltersList.push({ label: `Tel: ${colPhone}`, clear: () => clearColFilter('phone') });
        }
        if (collector) {
            let colText = 'Cobrador';
            const colSelEl = document.getElementById('filterCollector');
            if (colSelEl && colSelEl.selectedOptions[0]) {
                colText = colSelEl.selectedOptions[0].textContent.trim();
            }
            activeFiltersList.push({ label: `Cobrador: ${colText}`, clear: () => clearColFilter('collector') });
        }
        if (loanSt) {
            const stMap = { 'AL_CORRIENTE': 'Al corriente', 'ATRASADO': 'Atrasado', 'SIN_PRESTAMO': 'Sin préstamo' };
            activeFiltersList.push({ label: `Estado: ${stMap[loanSt] || loanSt}`, clear: () => clearColFilter('loan_status') });
        }
        if (nextDueDate || nextDueText) {
            activeFiltersList.push({ label: `Próx. pago: ${nextDueDate || nextDueText}`, clear: () => clearColFilter('next_due') });
        }
        if (marital) {
            activeFiltersList.push({ label: `Civil: ${marital}`, clear: () => { document.getElementById('filterMarital').value = ''; filterClients(); } });
        }

        if (activeFiltersList.length > 0) {
            activeFiltersList.forEach(function(item) {
                const chip = document.createElement('span');
                chip.className = 'filter-chip';
                chip.innerHTML = `<span>${item.label}</span><button type="button" class="filter-chip-remove" title="Quitar filtro">&times;</button>`;
                chip.querySelector('button').addEventListener('click', item.clear);
                chipsContainer.appendChild(chip);
            });
            activeBar.style.display = 'flex';
        } else {
            activeBar.style.display = 'none';
        }
    }

    // Texto de cantidad de clientes
    const countDisplay = document.getElementById('clientsCountText');
    if (countDisplay) {
        if (visibleCount < totalRows) {
            countDisplay.textContent = `${visibleCount} de ${totalRows} cliente(s)`;
        } else {
            countDisplay.textContent = `${visibleCount} cliente(s)`;
        }
    }
}
</script>
@endpush