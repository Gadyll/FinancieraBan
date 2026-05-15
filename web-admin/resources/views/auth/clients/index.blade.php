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
.client-num  { font-weight: 800; font-family: 'Courier New', monospace; color: var(--blue); font-size: .88rem; white-space: nowrap; }
.client-name { font-weight: 700; white-space: nowrap; }
.client-sub  { font-size: .80rem; color: var(--muted); margin-top: .1rem; }

/* Assign form inline */
.assign-inline {
    display: flex; gap: .4rem; align-items: center; flex-wrap: wrap;
}
.assign-inline select {
    flex: 1; min-width: 110px; padding: .38rem .65rem;
    border: 1.5px solid rgba(26,111,207,.18); border-radius: 8px;
    background: #fafcff; font-family: 'Outfit', sans-serif;
    font-size: .83rem; color: var(--text); outline: none;
}
.assign-inline select:focus { border-color: var(--blue); box-shadow: 0 0 0 2px rgba(26,111,207,.10); }

/* Loan status */
.loan-st { font-size: .76rem; font-weight: 700; }

/* Search bar */
.search-bar {
    padding: .85rem 1.25rem; border-bottom: 1px solid rgba(26,111,207,.10);
    background: #fafcff; display: flex; gap: .65rem; align-items: center; flex-wrap: wrap;
}

/* Action buttons row */
.act-row { display: flex; flex-direction: column; gap: .3rem; }
.act-btns { display: flex; gap: .3rem; flex-wrap: wrap; }

/* Modal overlay */
.modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(13,27,46,.55); backdrop-filter: blur(3px);
    z-index: 9000; align-items: center; justify-content: center; padding: 1rem;
}
.modal-overlay.open { display: flex; }
.modal-box {
    background: #fff; border-radius: 16px; width: 100%; max-width: 580px;
    box-shadow: 0 24px 80px rgba(13,27,46,.35); overflow: hidden;
    max-height: 90vh; overflow-y: auto;
}
.modal-head {
    background: linear-gradient(135deg,var(--blue),var(--blue-dark));
    color: #fff; padding: 1.1rem 1.4rem;
    display: flex; align-items: center; justify-content: space-between;
}
.modal-head h3 { font-weight: 900; font-size: 1rem; margin: 0; }
.modal-close {
    background: rgba(255,255,255,.15); border: none; color: #fff;
    width: 28px; height: 28px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 1.1rem; transition: .13s;
}
.modal-close:hover { background: rgba(255,255,255,.3); }
.modal-body { padding: 1.4rem; }
.modal-footer { padding: .9rem 1.4rem; background: #fafcff; border-top: 1px solid rgba(26,111,207,.10); display: flex; gap: .6rem; justify-content: flex-end; }

/* Danger modal */
.modal-danger .modal-head { background: linear-gradient(135deg,#e03a3a,#b02020); }
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
                                        <div class="act-row">
                                            {{-- Asignar cobrador --}}
                                            <form method="POST" action="{{ route('clients.assign', ['clientId'=>$cid]) }}">
                                                @csrf
                                                <div class="assign-inline">
                                                    <select name="user_id" required>
                                                        <option value="">Cobrador...</option>
                                                        @foreach($collectors as $u)
                                                            <option value="{{ $u['id'] }}"
                                                                {{ ($c['assigned_user_id']??null)==$u['id'] ? 'selected' : '' }}>
                                                                {{ $u['username'] }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <button class="btn btn-ghost btn-sm" type="submit">Asignar</button>
                                                </div>
                                            </form>
                                            {{-- Acciones: Préstamo / Editar / Eliminar --}}
                                            <div class="act-btns">
                                                <a href="{{ route('loans.create', ['client_id'=>$cid]) }}"
                                                   class="btn btn-primary btn-sm">
                                                    <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                                                    Préstamo
                                                </a>
                                                <button type="button" class="btn btn-ghost btn-sm"
                                                    onclick="openEdit({{ $cid }}, {{ json_encode($c) }})">
                                                    <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                    Editar
                                                </button>
                                                <button type="button" class="btn btn-sm" style="background:rgba(224,58,58,.08);color:var(--red);border:1px solid rgba(224,58,58,.2);"
                                                    onclick="openDelete({{ $cid }}, '{{ addslashes($c['full_name']??'') }}', {{ $loanSt !== null && $loanSt !== 'SIN_PRESTAMO' ? 'true' : 'false' }})">
                                                    <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path stroke-linecap="round" d="M19 6l-1 14H6L5 6M10 11v6M14 11v6M9 6V4h6v2"/></svg>
                                                    Borrar
                                                </button>
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
@endsection

{{-- ══ MODAL EDITAR ══ --}}
<div class="modal-overlay" id="editModal">
    <div class="modal-box">
        <div class="modal-head">
            <h3>✏️ Editar cliente</h3>
            <button class="modal-close" onclick="closeModal('editModal')">✕</button>
        </div>
        <form method="POST" id="editForm" action="" autocomplete="off">
            @csrf @method('PATCH')
            <div class="modal-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
                    <div class="field" style="grid-column:1/-1;">
                        <label class="field-label field-required">Nombre completo</label>
                        <input class="field-input" id="e_full_name" name="full_name" maxlength="150" required>
                    </div>
                    <div class="field">
                        <label class="field-label field-required">Teléfono</label>
                        <input class="field-input digits-only" id="e_phone" name="phone" maxlength="10" required>
                    </div>
                    <div class="field">
                        <label class="field-label">Estado civil</label>
                        <select class="field-input field-select" id="e_marital" name="marital_status">
                            @foreach($maritalOptions as $opt)
                                <option value="{{ $opt }}">{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field" style="grid-column:1/-1;">
                        <label class="field-label field-required">Dirección</label>
                        <input class="field-input" id="e_address" name="address" maxlength="255" required>
                    </div>
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
                    <div class="field">
                        <label class="field-label">Ingreso mensual</label>
                        <input class="field-input" type="number" step="0.01" id="e_income" name="monthly_income" min="0">
                    </div>
                    <div style="grid-column:1/-1;padding-top:.5rem;border-top:1px solid rgba(26,111,207,.10);margin-top:.25rem;">
                        <div style="font-size:.75rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--blue);margin-bottom:.6rem;">Datos del Aval</div>
                    </div>
                    <div class="field">
                        <label class="field-label field-required">Nombre aval</label>
                        <input class="field-input" id="e_gname" name="guarantor_full_name" maxlength="150" required>
                    </div>
                    <div class="field">
                        <label class="field-label field-required">Teléfono aval</label>
                        <input class="field-input digits-only" id="e_gphone" name="guarantor_phone" maxlength="10" required>
                    </div>
                    <div class="field" style="grid-column:1/-1;">
                        <label class="field-label field-required">Dirección aval</label>
                        <input class="field-input" id="e_gaddress" name="guarantor_address" maxlength="255" required>
                    </div>
                    <div class="field">
                        <label class="field-label">Estado civil aval</label>
                        <select class="field-input field-select" id="e_gmarital" name="guarantor_marital_status">
                            @foreach($maritalOptions as $opt)
                                <option value="{{ $opt }}">{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('editModal')">Cancelar</button>
                <button type="submit" class="btn btn-primary">💾 Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

{{-- ══ MODAL ELIMINAR ══ --}}
<div class="modal-overlay modal-danger" id="deleteModal">
    <div class="modal-box" style="max-width:420px;">
        <div class="modal-head">
            <h3>🗑️ Eliminar cliente</h3>
            <button class="modal-close" onclick="closeModal('deleteModal')">✕</button>
        </div>
        <div class="modal-body">
            <p style="font-size:.95rem;margin-bottom:.85rem;">¿Eliminar a <strong id="delName"></strong>?</p>
            <div id="delLoanWarn" style="display:none;background:rgba(224,58,58,.07);border:1px solid rgba(224,58,58,.25);border-radius:10px;padding:.85rem 1rem;font-size:.88rem;color:var(--red);">
                ⚠️ <strong>Este cliente tiene préstamos.</strong><br>No se puede eliminar, solo editar sus datos.
            </div>
            <div id="delConfirmMsg" style="font-size:.88rem;color:var(--muted);">Esta acción es irreversible. Se eliminará el cliente y su aval.</div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" onclick="closeModal('deleteModal')">Cancelar</button>
            <form id="deleteForm" method="POST" action="" style="display:inline;">
                @csrf @method('DELETE')
                <button type="submit" id="delBtn" class="btn" style="background:var(--red);color:#fff;">Eliminar definitivamente</button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function closeModal(id){ document.getElementById(id).classList.remove('open'); }
function openModal(id){ document.getElementById(id).classList.add('open'); }
document.querySelectorAll('.modal-overlay').forEach(function(el){
    el.addEventListener('click', function(e){ if(e.target===this) closeModal(this.id); });
});
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
    for(var i=0;i<ms.options.length;i++) ms.options[i].selected = ms.options[i].value===(data.marital_status||'');
    var g = data.guarantor || {};
    document.getElementById('e_gname').value    = g.full_name    || '';
    document.getElementById('e_gphone').value   = g.phone        || '';
    document.getElementById('e_gaddress').value = g.address      || '';
    var gm = document.getElementById('e_gmarital');
    for(var i=0;i<gm.options.length;i++) gm.options[i].selected = gm.options[i].value===(g.marital_status||'');
    openModal('editModal');
}
function openDelete(id, name, hasLoans){
    document.getElementById('delName').textContent         = name;
    document.getElementById('delLoanWarn').style.display   = hasLoans ? 'block' : 'none';
    document.getElementById('delConfirmMsg').style.display = hasLoans ? 'none'  : 'block';
    document.getElementById('delBtn').style.display        = hasLoans ? 'none'  : '';
    document.getElementById('deleteForm').action = '/clients/' + id;
    openModal('deleteModal');
}
document.querySelectorAll('.digits-only').forEach(function(el){
    el.addEventListener('input', function(){ this.value = this.value.replace(/\D/g,'').slice(0,10); });
});
var si   = document.getElementById('clientSearch');
var rows = document.querySelectorAll('#clientsTable tbody tr[data-name]');
if(si){
    si.addEventListener('input', function(){
        var q = this.value.toLowerCase().trim();
        rows.forEach(function(row){ row.style.display = (!q || row.getAttribute('data-name').includes(q)) ? '' : 'none'; });
    });
}
</script>
@endpush