@extends('layouts.app')

@section('title', 'Clientes - MYBANK')

@push('styles')
<style>
  .surfacex{
    background:#fff;
    border:1px solid rgba(26,111,207,.14);
    border-radius:16px;
    box-shadow:0 8px 28px rgba(13,27,46,.08);
  }
  .surfacex-pad{ padding:1rem 1.1rem; }

  .grid2{ display:grid; grid-template-columns: 1fr 1fr; gap:.85rem; }
  @media (max-width: 992px){ .grid2{ grid-template-columns: 1fr; } }

  .field-label{
    display:block;
    font-weight:900;
    font-size:.86rem;
    color:#3a4d65;
    margin-bottom:.35rem;
  }

  .field-input{
    width:100%;
    padding:.70rem .9rem;
    border:1.5px solid rgba(26,111,207,.16);
    border-radius:12px;
    background:#f8faff;
    outline:none;
    transition:.15s;
  }

  .field-input:focus{
    background:#fff;
    border-color: rgba(26,111,207,.45);
    box-shadow:0 0 0 3px rgba(26,111,207,.12);
  }

  .btnx{
    border:0;
    border-radius:12px;
    padding:.70rem 1rem;
    font-weight:900;
    cursor:pointer;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:.45rem;
    transition:.15s;
    white-space:nowrap;
    min-width:140px;
    text-decoration:none;
  }

  .btnx-primary{
    color:#fff;
    background: linear-gradient(135deg, #1a6fcf 0%, #1259b0 100%);
    box-shadow: 0 4px 18px rgba(26,111,207,.25);
  }

  .btnx-primary:hover{
    filter:brightness(1.06);
    transform: translateY(-1px);
  }

  .btnx-soft{
    background: rgba(26,111,207,.08);
    border: 1px solid rgba(26,111,207,.18);
    color:#1a6fcf;
  }

  .table-wrap{
    border-radius:16px;
    overflow:auto;
    border: 1px solid rgba(26,111,207,.14);
  }

  table{
    width:100%;
    border-collapse: collapse;
    min-width:1080px;
  }

  th,td{
    padding:.8rem .85rem;
    border-bottom: 1px solid rgba(26,111,207,.10);
    vertical-align: middle;
  }

  th{
    font-size:.78rem;
    letter-spacing:.10em;
    text-transform: uppercase;
    color:#6b7e96;
    background: rgba(26,111,207,.04);
    font-weight:900;
  }

  tr:hover td{
    background: rgba(26,111,207,.03);
  }

  .badgex{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:7px 12px;
    border-radius:999px;
    border:1.5px solid rgba(15,23,42,.14);
    font-weight:1000;
    font-size:13px;
    letter-spacing:.02em;
    line-height:1;
    white-space:nowrap;
  }

  .b-ok{
    background: rgba(18,169,138,.10);
    color:#065F46;
    border-color: rgba(18,169,138,.30);
  }

  .b-bad{
    background: rgba(224,58,58,.10);
    color:#B91C1C;
    border-color: rgba(224,58,58,.30);
  }

  .b-none{
    background: rgba(26,111,207,.08);
    color:#1a6fcf;
    border-color: rgba(26,111,207,.22);
  }

  .assign-row{
    display:flex;
    gap:.6rem;
    align-items:center;
    flex-wrap:wrap;
  }

  .assign-row select{
    min-width:220px;
  }

  .mono{
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
  }

  .muted{ color:#6b7e96; }
  .section-title{ margin:0; font-weight:1000; }
  .help{ color:#6b7e96; margin:.25rem 0 0 0; }
  .hrx{ height:1px; background: rgba(26,111,207,.10); margin:1rem 0; border:0; }
</style>
@endpush

@section('content')
@php
    $errorText = $error ?? null;
    $maritalOptions = $maritalOptions ?? ['SOLTERO','CASADO','UNION LIBRE','VIUDO','DIVORCIADO'];
@endphp

<div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
    <div>
        <h1 class="page-title mb-1">Clientes</h1>
        <p class="page-sub mb-0">Crea, edita y asigna clientes a cobradores. Incluye AVAL obligatorio y estatus de pagos.</p>
    </div>

    <a href="{{ route('dashboard') }}" class="btnx btnx-soft">
        Volver
    </a>
</div>

@if($errorText)
    <div class="alert alert-danger mb-3">{{ $errorText }}</div>
@endif

@if(session('success'))
    <div class="alert alert-success mb-3">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger mb-3">
        {{ $errors->first() }}
    </div>
@endif

<div class="row g-3">
    <div class="col-12 col-lg-5">
        <div class="surfacex surfacex-pad">
            <h2 class="section-title">Crear cliente</h2>
            <p class="help">Campos obligatorios: cliente + aval.</p>

            <form method="POST" action="{{ route('clients.store') }}" autocomplete="off">
                @csrf

                <hr class="hrx">
                <h3 class="section-title" style="font-size:1rem;">Datos del cliente</h3>

                <div class="grid2" style="margin-top:.8rem;">
                    <div>
                        <label class="field-label">Número de cliente</label>
                        <input class="field-input" name="client_number" value="{{ old('client_number') }}" placeholder="C0001" required>
                    </div>

                    <div>
                        <label class="field-label">Teléfono (10 dígitos)</label>
                        <input class="field-input" name="phone" value="{{ old('phone') }}" placeholder="4421234567" required>
                    </div>
                </div>

                <div style="margin-top:.85rem;">
                    <label class="field-label">Nombre completo</label>
                    <input class="field-input" name="full_name" value="{{ old('full_name') }}" placeholder="Juan Pérez" required>
                </div>

                <div style="margin-top:.85rem;">
                    <label class="field-label">Dirección</label>
                    <input class="field-input" name="address" value="{{ old('address') }}" placeholder="Calle, número, colonia, municipio" required>
                </div>

                <div class="grid2" style="margin-top:.85rem;">
                    <div>
                        <label class="field-label">Estado civil</label>
                        <select class="field-input" name="marital_status" required>
                            <option value="">Selecciona</option>
                            @foreach($maritalOptions as $op)
                                <option value="{{ $op }}" @selected(old('marital_status') === $op)>{{ $op }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="field-label">Nombre del cónyuge (opcional)</label>
                        <input class="field-input" name="spouse_full_name" value="{{ old('spouse_full_name') }}" placeholder="Solo si aplica">
                    </div>
                </div>

                <hr class="hrx">
                <h3 class="section-title" style="font-size:1rem;">Datos del aval (obligatorio)</h3>

                <div style="margin-top:.8rem;">
                    <label class="field-label">Nombre completo del aval</label>
                    <input class="field-input" name="guarantor_full_name" value="{{ old('guarantor_full_name') }}" placeholder="Nombre del aval" required>
                </div>

                <div style="margin-top:.85rem;">
                    <label class="field-label">Dirección del aval</label>
                    <input class="field-input" name="guarantor_address" value="{{ old('guarantor_address') }}" placeholder="Dirección completa" required>
                </div>

                <div class="grid2" style="margin-top:.85rem;">
                    <div>
                        <label class="field-label">Teléfono del aval (10 dígitos)</label>
                        <input class="field-input" name="guarantor_phone" value="{{ old('guarantor_phone') }}" placeholder="4421234567" required>
                    </div>

                    <div>
                        <label class="field-label">Estado civil del aval</label>
                        <select class="field-input" name="guarantor_marital_status" required>
                            <option value="">Selecciona</option>
                            @foreach($maritalOptions as $op)
                                <option value="{{ $op }}" @selected(old('guarantor_marital_status') === $op)>{{ $op }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div style="margin-top:1rem;">
                    <button class="btnx btnx-primary" type="submit" style="width:100%;">
                        Crear cliente
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-12 col-lg-7">
        <div class="surfacex surfacex-pad">
            <h2 class="section-title">Lista de clientes</h2>
            <p class="help">Asignación + estatus de pagos.</p>

            <div class="table-wrap" style="margin-top:.85rem;">
                <table>
                    <thead>
                        <tr>
                            <th style="width:70px;">ID</th>
                            <th style="width:110px;">Número</th>
                            <th>Nombre</th>
                            <th style="width:130px;">Teléfono</th>
                            <th style="width:150px;">Asignado a</th>
                            <th style="width:150px;">Estatus</th>
                            <th style="width:160px;">Próximo pago</th>
                            <th style="width:270px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clients as $c)
                            @php
                                $cid = $c['id'] ?? null;
                                $assigned = $c['assigned_username'] ?? null;
                                $loanStatus = $c['loan_status'] ?? null;
                                $overdue = (int)($c['overdue_count'] ?? 0);
                                $nextDue = $c['next_due_date'] ?? null;

                                $badgeClass = 'b-none';
                                $label = 'SIN PRESTAMO';

                                if ($loanStatus === 'AL_CORRIENTE') {
                                    $badgeClass = 'b-ok';
                                    $label = 'AL CORRIENTE';
                                }

                                if ($loanStatus === 'ATRASADO') {
                                    $badgeClass = 'b-bad';
                                    $label = 'ATRASADO';
                                }
                            @endphp

                            <tr>
                                <td class="mono">{{ $cid ?? '—' }}</td>
                                <td class="fw-semibold">{{ $c['client_number'] ?? '—' }}</td>
                                <td>{{ $c['full_name'] ?? '—' }}</td>
                                <td class="text-nowrap">{{ $c['phone'] ?? '—' }}</td>

                                <td>
                                    @if($assigned)
                                        <strong>{{ $assigned }}</strong>
                                    @else
                                        <span class="muted">No asignado</span>
                                    @endif
                                </td>

                                <td>
                                    <span class="badgex {{ $badgeClass }}">
                                        {{ $label }}
                                        @if($loanStatus === 'ATRASADO')
                                            <span class="mono">({{ $overdue }})</span>
                                        @endif
                                    </span>
                                </td>

                                <td class="mono">
                                    {{ $nextDue ? $nextDue : '—' }}
                                </td>

                                <td>
                                    @if(!$cid)
                                        <span class="muted">Sin ID</span>
                                    @else
                                        <form method="POST" action="{{ route('clients.assign', ['clientId' => $cid]) }}" class="assign-row">
                                            @csrf

                                            <select name="user_id" class="field-input" required>
                                                <option value="">Selecciona cobrador</option>
                                                @foreach($collectors as $u)
                                                    <option value="{{ $u['id'] }}">
                                                        {{ $u['username'] }} (ID {{ $u['id'] }})
                                                    </option>
                                                @endforeach
                                            </select>

                                            <button class="btnx btnx-soft" type="submit">
                                                Asignar
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 muted">Sin clientes</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection