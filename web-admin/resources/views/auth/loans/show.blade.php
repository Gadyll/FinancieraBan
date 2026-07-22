@extends('layouts.app')
@section('title', 'Préstamo #{{ $loan["id"] ?? "" }} — MYBANK')

@push('styles')
<style>
/* ── Desglose financiero ── */
.breakdown {
    background: linear-gradient(135deg, rgba(26,111,207,.04), rgba(13,184,138,.04));
    border: 1.5px solid rgba(26,111,207,.15);
    border-radius: 12px; padding: 1.1rem 1.25rem;
}
.bk-row {
    display: flex; justify-content: space-between; align-items: baseline;
    padding: .35rem 0; border-bottom: 1px dashed rgba(26,111,207,.08);
    gap: 1rem;
}
.bk-row:last-child { border-bottom: 0; border-top: 2px solid rgba(26,111,207,.15); padding-top: .65rem; margin-top: .25rem; }
.bk-label { color: var(--muted); font-size: .91rem; font-weight: 600; }
.bk-val   { font-weight: 800; font-variant-numeric: tabular-nums; }
.bk-row.total .bk-label { color: var(--text); font-weight: 800; }
.bk-row.total .bk-val   { color: var(--blue); font-size: 1.15rem; }

/* ── Summary boxes ── */
.sum-boxes {
    display: grid; grid-template-columns: repeat(3,1fr); gap: .85rem;
    margin-bottom: 1.1rem;
}
@media(max-width:640px){ .sum-boxes{ grid-template-columns:1fr; } }
.sbox {
    padding: 1rem 1.1rem; border-radius: 12px;
    border: 1.5px solid rgba(26,111,207,.14); background: #fff;
    text-align: center;
}
.sbox-label { font-size:.74rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--muted); }
.sbox-val   { font-size:1.55rem;font-weight:900;margin:.2rem 0; font-variant-numeric:tabular-nums; }
.sbox-sub   { font-size:.80rem;color:var(--muted); }
.sbox.b-total  { border-color:rgba(26,111,207,.28); }
.sbox.b-total .sbox-val { color:var(--blue); }
.sbox.b-paid   { border-color:rgba(13,184,138,.28); }
.sbox.b-paid .sbox-val  { color:#0a8f6b; }
.sbox.b-late   { border-color:rgba(224,58,58,.28); }
.sbox.b-late .sbox-val  { color:var(--red); }
.sbox.b-ok     { border-color:rgba(13,184,138,.28); }
.sbox.b-ok .sbox-val    { color:#0a8f6b; }

/* ── Timeline ── */
.timeline-scroll { max-height: 500px; overflow-y: auto; padding: .25rem .5rem; }
.tl-item {
    display: grid; grid-template-columns: 32px 1fr auto;
    align-items: center; gap: .75rem; padding: .6rem .5rem;
    border-radius: 10px; border: 1px solid transparent; margin-bottom: .2rem;
    transition: .1s;
}
.tl-item:hover { background: rgba(26,111,207,.03); border-color: rgba(26,111,207,.08); }
.tl-dot {
    width: 26px; height: 26px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .75rem; font-weight: 800; flex-shrink: 0;
}

/* Payment form */
.pay-form {
    background: #fff; border: 1.5px solid rgba(26,111,207,.18);
    border-radius: 12px; padding: 1.25rem; margin-bottom: 1rem;
}
.pay-form-title {
    font-weight: 800; font-size: .95rem; margin-bottom: 1rem;
    display: flex; align-items: center; gap: .5rem; color: var(--text);
}

/* KV list */
.kv-list { display: grid; grid-template-columns: 140px 1fr; gap: .35rem .75rem; font-size: .91rem; }
.kv-list .kv-k { color: var(--muted); font-weight: 700; }
.kv-list .kv-v { font-weight: 600; }
</style>
@endpush

@section('content')
@php
    $loanId    = $loan['id'] ?? '—';
    $status    = $loan['status'] ?? 'ACTIVE';
    $statusCls = match($status){ 'ACTIVE'=>'badge-blue','PAID'=>'badge-teal','LATE'=>'badge-red',default=>'badge-gray' };
    $statusLbl = match($status){ 'ACTIVE'=>'Activo','PAID'=>'Pagado','LATE'=>'Vencido',default=>'Cancelado' };
    $freqLbl   = match($loan['frequency']??''){ 'WEEKLY'=>'Semanal','BIWEEKLY'=>'Quincenal','MONTHLY'=>'Mensual','YEARLY'=>'Anual',default=>$loan['frequency']??'—' };

    $principal    = (float)($loan['principal_amount'] ?? 0);
    $interestAmt  = (float)($loan['interest_amount']  ?? 0);
    $interestRate = (float)($loan['interest_rate']    ?? 0);
    $total        = (float)($loan['total_amount']     ?? 0);
    $cuota        = (float)($loan['payment_amount']   ?? 0);
    $nPagos       = (int)($loan['payments_count']     ?? 0);

    $totalPaid = (float)($summary['total_paid'] ?? 0);
    $remaining = (float)($summary['remaining'] ?? max(0, $total - $totalPaid));
    $overdue   = (int)($summary['overdue_installments'] ?? 0);

    $schedule  = $loan['schedule'] ?? [];
    $clientName= $client['full_name'] ?? 'Cliente #'.($loan['client_id']??'');
    $paid_pct  = $total > 0 ? min(100, round(($totalPaid / $total)*100, 1)) : 0;
@endphp

{{-- Header --}}
<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <span class="breadcrumb-sep">›</span>
            <a href="{{ route('loans.index') }}">Préstamos</a>
            <span class="breadcrumb-sep">›</span>
            #{{ $loanId }}
        </div>
        <h1 class="page-title">
            Préstamo #{{ $loanId }}
            <span class="badge {{ $statusCls }}" style="font-size:.65rem;vertical-align:middle;margin-left:.5rem;">
                <span class="badge-dot" style="background:currentColor;opacity:.7;"></span>
                {{ $statusLbl }}
            </span>
        </h1>
        <p class="page-sub">{{ $clientName }} · {{ $freqLbl }} · Inicio: {{ $loan['start_date']??'—' }}</p>
    </div>
    <div style="display:flex;gap:.6rem;flex-wrap:wrap;">
        <a href="{{ route('loans.create', ['client_id'=>$loan['client_id']??'']) }}" class="btn btn-ghost">
            Nuevo ciclo
        </a>
        <a href="{{ route('loans.index') }}" class="btn btn-ghost">← Préstamos</a>
    </div>
</div>

{{-- Flash messages --}}
@if(session('payment_ok'))
    <div style="
        background: linear-gradient(135deg,#0b9272,#0a7a60);
        border-radius:14px; padding:1.1rem 1.25rem;
        margin-bottom:1.1rem;
        display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;
        box-shadow: 0 6px 20px rgba(11,146,114,.30);
        color:#fff;">
        <div style="display:flex;align-items:center;gap:.75rem;">
            <div style="width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,.15);
                        display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <div style="font-weight:800;font-size:.95rem;">¡Pago registrado con éxito!</div>
                <div style="font-size:.83rem;opacity:.85;">{{ session('payment_ok') }}</div>
            </div>
        </div>
        <a href="{{ route('loans.ticket', ['loanId'=>$loanId]) }}?autoprint=1"
           target="_blank"
           style="display:inline-flex;align-items:center;gap:.5rem;
                  background:#fff;color:#0b9272;
                  padding:.65rem 1.25rem;border-radius:10px;
                  font-weight:800;font-size:.88rem;text-decoration:none;
                  box-shadow:0 3px 10px rgba(0,0,0,.15);white-space:nowrap;
                  transition:.15s;"
           onmouseover="this.style.background='#f0fdf9'"
           onmouseout="this.style.background='#fff'">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                <path stroke-linecap="round" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
                <rect x="6" y="14" width="12" height="8" rx="1"/>
            </svg>
            Imprimir ticket
        </a>
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <div class="alert-icon">⚠</div>
        {{ $errors->first() }}
    </div>
@endif

<div class="row g-3">
<div class="col-12 col-lg-7">

    {{-- ── Resumen numérico ── --}}
    <div class="sum-boxes">
        <div class="sbox b-total">
            <div class="sbox-label">Total del crédito</div>
            <div class="sbox-val">${{ number_format($total,0) }}</div>
            <div class="sbox-sub">{{ $nPagos }} cuotas</div>
        </div>
        <div class="sbox b-paid">
            <div class="sbox-label">Pagado</div>
            <div class="sbox-val">${{ number_format($totalPaid,0) }}</div>
            <div class="sbox-sub">{{ $paid_pct }}% del total</div>
        </div>
        <div class="sbox {{ $overdue>0 ? 'b-late' : 'b-ok' }}">
            <div class="sbox-label">{{ $overdue>0 ? 'Vencidas' : 'Restante' }}</div>
            <div class="sbox-val">{{ $overdue>0 ? $overdue.' cuota(s)' : '$'.number_format(max(0,$remaining),0) }}</div>
            <div class="sbox-sub">{{ $overdue>0 ? 'Sin pagar' : 'por liquidar' }}</div>
        </div>
    </div>

    {{-- Barra de progreso --}}
    <div class="mb-3" style="background:rgba(26,111,207,.07);border-radius:999px;height:10px;overflow:hidden;">
        <div style="width:{{ $paid_pct }}%;height:100%;background:linear-gradient(90deg,var(--teal),var(--blue));border-radius:999px;transition:width .5s;"></div>
    </div>

    {{-- Desglose financiero --}}
    <div class="breakdown mb-3">
        <div style="font-size:.78rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);margin-bottom:.6rem;">
            Desglose financiero
        </div>
        <div class="bk-row">
            <span class="bk-label">💰 Capital prestado</span>
            <span class="bk-val">${{ number_format($principal,2) }}</span>
        </div>
        @if($interestRate > 0)
        <div class="bk-row">
            <span class="bk-label">📊 Tasa de interés</span>
            <span class="bk-val" style="color:var(--blue);">{{ number_format($interestRate,2) }}%</span>
        </div>
        @endif
        <div class="bk-row">
            <span class="bk-label">📈 Interés generado</span>
            <span class="bk-val" style="color:var(--blue);">+ ${{ number_format($interestAmt,2) }}</span>
        </div>
        <div class="bk-row total">
            <span class="bk-label">TOTAL CONGELADO</span>
            <span class="bk-val">${{ number_format($total,2) }}</span>
        </div>
    </div>

    {{-- Datos del préstamo --}}
    <div class="card mb-3">
        <div class="card-head card-accent-blue">
            <h2 class="card-title">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Datos del préstamo
            </h2>
        </div>
        <div class="card-body">
            <div class="kv-list">
                <span class="kv-k">Cliente</span>      <span class="kv-v">{{ $clientName }}</span>
                <span class="kv-k">Ciclo</span>        <span class="kv-v"><span class="badge badge-gray">C{{ $loan['cycle_number']??'?' }}</span></span>
                <span class="kv-k">Frecuencia</span>   <span class="kv-v">{{ $freqLbl }}</span>
                <span class="kv-k">N° pagos</span>     <span class="kv-v">{{ $nPagos }}</span>
                <span class="kv-k">Cuota / pago</span> <span class="kv-v mono" style="font-weight:800;color:var(--teal);">${{ number_format($cuota,2) }}</span>
                <span class="kv-k">Fecha inicio</span> <span class="kv-v mono">{{ $loan['start_date']??'—' }}</span>
                <span class="kv-k">Estado</span>       <span class="kv-v"><span class="badge {{ $statusCls }}">{{ $statusLbl }}</span></span>
            </div>
        </div>
    </div>

    {{-- Historial de pagos --}}
    <div class="card">
        <div class="card-head card-accent-teal">
            <h2 class="card-title">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2m-3 7l2 2 4-4"/>
                </svg>
                Historial de pagos
                <span class="badge badge-teal" style="margin-left:.35rem;">{{ count($payments) }}</span>
            </h2>
        </div>
        <div class="card-body-flush">
            @if(count($payments) > 0)
                <div class="table-wrap">
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th style="width:36px;">#</th>
                                <th>Ticket</th>
                                <th class="cell-right">Monto</th>
                                <th>Método</th>
                                <th>Cobrador</th>
                                <th>Fecha</th>
                                <th style="width:50px;text-align:center;">🖨️</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payments as $idx => $pay)
                                <tr class="row-paid">
                                    <td class="cell-muted" style="font-size:.80rem;">{{ $idx + 1 }}</td>
                                    <td class="cell-mono cell-muted">{{ $pay['ticket_number']??'—' }}</td>
                                    <td class="cell-right cell-money text-teal">
                                        ${{ number_format((float)($pay['amount_paid']??0),2) }}
                                    </td>
                                    <td>
                                        <span class="badge badge-gray">{{ $pay['payment_method']??'—' }}</span>
                                    </td>
                                    <td>{{ $pay['collector_username']??'—' }}</td>
                                    <td class="cell-mono cell-muted">{{ substr($pay['paid_at']??'',0,16) }}</td>
                                    <td style="text-align:center;">
                                        <a href="{{ route('loans.ticket', ['loanId'=>$loanId]) }}?payment_id={{ $pay['id']??'' }}"
                                           target="_blank"
                                           title="Imprimir ticket #{{ $pay['ticket_number']??'' }}"
                                           style="display:inline-flex;align-items:center;justify-content:center;
                                                  width:30px;height:30px;border-radius:8px;
                                                  background:rgba(26,111,207,.08);color:var(--blue);
                                                  text-decoration:none;transition:.15s;"
                                           onmouseover="this.style.background='rgba(26,111,207,.18)'"
                                           onmouseout="this.style.background='rgba(26,111,207,.08)'">
                                            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
                                                <rect x="6" y="14" width="12" height="8" rx="1"/>
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div style="text-align:center;padding:2rem;color:var(--muted);">Sin pagos registrados.</div>
            @endif
        </div>
    </div>

</div>

{{-- ── Columna derecha ── --}}
<div class="col-12 col-lg-5">

    {{-- Formulario de pago --}}
    @if(in_array($status, ['ACTIVE','LATE']))
        <div class="pay-form">
            <div class="pay-form-title">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="var(--teal)" stroke-width="2">
                    <path stroke-linecap="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                Registrar pago
            </div>
            @if($errors->has('surcharge'))
                <div class="alert alert-danger" style="margin-bottom:.75rem;">{{ $errors->first('surcharge') }}</div>
            @endif
            <form method="POST" action="{{ route('loans.pay', ['loanId'=>$loanId]) }}">
                @csrf
                <div class="grid-2 mb-2">
                    <div class="field">
                        <label class="field-label field-required">Monto recibido ($)</label>
                        <input class="field-input" name="amount_paid" type="number"
                               min="0.01" step="0.01"
                               placeholder="{{ number_format($cuota,2) }}" required>
                        <div class="field-hint">Cuota normal: ${{ number_format($cuota,2) }}</div>
                    </div>
                    <div class="field">
                        <label class="field-label field-required">Método de pago</label>
                        <select class="field-input field-select" name="payment_method" required>
                            <option value="CASH" selected>💵 Efectivo</option>
                            <option value="TRANSFER">🏦 Transferencia</option>
                            <option value="CARD">💳 Tarjeta</option>
                            <option value="OTHER">Otro</option>
                        </select>
                    </div>
                </div>
                <div class="field">
                    <label class="field-label">Notas (opcional)</label>
                    <input class="field-input" name="notes" type="text" maxlength="255" placeholder="Observaciones...">
                </div>
                <button type="submit" class="btn btn-teal btn-full">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    Registrar y generar ticket
                </button>
            </form>
        </div>

        {{-- ── Recargo por mora (solo admin) ── --}}
        <div class="pay-form" style="border-color:rgba(220,38,38,.25);background:rgba(220,38,38,.02);">
            <div class="pay-form-title" style="color:var(--red);">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="var(--red)" stroke-width="2">
                    <path stroke-linecap="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Recargo por mora — Solo Admin
            </div>

            @if($errors->has('surcharge_pay'))
                <div class="alert alert-danger" style="margin-bottom:.75rem;font-size:.85rem;">
                    {{ $errors->first('surcharge_pay') }}
                </div>
            @endif

            {{-- Recargos existentes (pendientes + liquidados) --}}
            @php
                $allSurcharges = collect($surcharges ?? []);
                $pendingSc = $allSurcharges->where('status','PENDING');
                $paidSc    = $allSurcharges->where('status','PAID');
            @endphp

            @if($allSurcharges->count() > 0)
                <div style="margin-bottom:.85rem;">
                    @foreach($allSurcharges as $sc)
                        @php $isPending = $sc['status'] === 'PENDING'; @endphp
                        <div style="
                            display:flex; justify-content:space-between; align-items:center;
                            flex-wrap:wrap; gap:.5rem;
                            padding:.6rem .75rem;
                            background:{{ $isPending ? 'rgba(220,38,38,.06)' : 'rgba(11,146,114,.05)' }};
                            border-radius:10px;
                            border:1px solid {{ $isPending ? 'rgba(220,38,38,.20)' : 'rgba(11,146,114,.18)' }};
                            margin-bottom:.4rem;">

                            {{-- Datos del recargo --}}
                            <div style="flex:1;min-width:0;">
                                <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;">
                                    <strong style="color:{{ $isPending ? 'var(--red)' : '#0b9272' }};font-size:.95rem;">
                                        ${{ number_format((float)$sc['amount'],2) }}
                                    </strong>
                                    <span class="badge {{ $isPending ? 'badge-orange' : 'badge-teal' }}" style="font-size:.72rem;">
                                        {{ $isPending ? 'Pendiente' : '✓ Liquidado' }}
                                    </span>
                                </div>
                                @if(!empty($sc['reason']))
                                    <div style="font-size:.80rem;color:var(--text-2);margin-top:.15rem;">
                                        {{ $sc['reason'] }}
                                    </div>
                                @endif
                                <div style="font-size:.75rem;color:var(--muted);margin-top:.1rem;">
                                    Autorizado: {{ \Carbon\Carbon::parse($sc['created_at'])->format('d/m/Y H:i') }}
                                </div>
                            </div>

                            {{-- Botón Liquidar (solo si está pendiente) --}}
                            @if($isPending)
                                <form method="POST"
                                      action="{{ route('loans.surcharge.pay', ['loanId'=>$loanId,'surchargeId'=>$sc['id']]) }}"
                                      onsubmit="return confirm('¿Confirmas liquidar este recargo de ${{ number_format((float)$sc['amount'],2) }}?\n\nEsto NO modifica el préstamo principal.')">
                                    @csrf
                                    <button type="submit"
                                            style="display:inline-flex;align-items:center;gap:.4rem;
                                                   padding:.5rem 1rem;border-radius:8px;border:0;cursor:pointer;
                                                   background:#0b9272;color:#fff;
                                                   font-family:'Outfit',sans-serif;font-weight:800;font-size:.83rem;
                                                   box-shadow:0 3px 10px rgba(11,146,114,.30);
                                                   transition:.15s;"
                                            onmouseover="this.style.background='#0a7a60'"
                                            onmouseout="this.style.background='#0b9272'">
                                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Liquidar
                                    </button>
                                </form>
                            @else
                                <div style="font-size:.78rem;color:#0b9272;font-weight:700;">
                                    ✓ Cobrado
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Formulario para nuevo recargo --}}
            <div style="border-top:1px dashed rgba(220,38,38,.20);padding-top:.85rem;margin-top:.5rem;">
                <div style="font-size:.76rem;font-weight:800;text-transform:uppercase;
                            color:var(--red);letter-spacing:.07em;margin-bottom:.6rem;">
                    + Autorizar nuevo recargo
                </div>
                <p style="font-size:.83rem;color:var(--text-2);margin-bottom:.75rem;">
                    Define el monto extra. El cobrador podrá liquidarlo sin afectar el préstamo.
                </p>
                @if($errors->has('surcharge'))
                    <div style="font-size:.82rem;color:var(--red);margin-bottom:.6rem;">
                        {{ $errors->first('surcharge') }}
                    </div>
                @endif
                <form method="POST" action="{{ route('loans.surcharge', ['loanId'=>$loanId]) }}">
                    @csrf
                    <div class="grid-2 mb-2">
                        <div class="field">
                            <label class="field-label field-required" style="color:var(--red);">Monto ($)</label>
                            <input class="field-input" name="amount" type="number"
                                   min="0.01" step="0.01" placeholder="0.00" required
                                   style="border-color:rgba(220,38,38,.28);">
                        </div>
                        <div class="field">
                            <label class="field-label">Motivo</label>
                            <input class="field-input" name="reason" type="text"
                                   maxlength="255" placeholder="Mora, penalización..."
                                   style="border-color:rgba(220,38,38,.18);">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-full"
                            style="background:var(--red);color:#fff;border:0;border-radius:10px;
                                   padding:.65rem;font-weight:800;cursor:pointer;">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" style="display:inline;vertical-align:middle;margin-right:.3rem;">
                            <path stroke-linecap="round" d="M12 5v14M5 12h14"/>
                        </svg>
                        Autorizar recargo
                    </button>
                </form>
            </div>
        </div>
    @endif

    {{-- Calendario de pagos --}}
    <div class="card">
        <div class="card-head card-accent-purple">
            <h2 class="card-title">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                Calendario de pagos
                <span class="badge badge-purple" style="margin-left:.35rem;">{{ count($schedule) }}</span>
            </h2>
            <div style="font-size:.77rem;color:rgba(255,255,255,.65);font-weight:500;">
                Días hábiles Lun–Vie · El gestor puede autorizar Sáb/Dom
            </div>
        </div>
        <div class="card-body" style="padding:.75rem;">
            <div class="timeline-scroll">
                @php
                    $dias = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
                @endphp
                @forelse($schedule as $s)
                    @php
                        $sStatus  = $s['status'] ?? 'PENDING';
                        $dotClass = match($sStatus){ 'PAID'=>'tl-paid','PARTIAL'=>'tl-partial','LATE'=>'tl-late',default=>'tl-pending' };
                        $badgeCls = match($sStatus){ 'PAID'=>'badge-teal','PARTIAL'=>'badge-orange','LATE'=>'badge-red',default=>'badge-blue' };
                        $badgeLbl = match($sStatus){ 'PAID'=>'✓ Pagada','PARTIAL'=>'~ Parcial','LATE'=>'⚠ Vencida',default=>'Pendiente' };
                        $dueObj   = !empty($s['due_date']) ? \Carbon\Carbon::parse($s['due_date']) : null;
                        $diaNom   = $dueObj ? $dias[$dueObj->dayOfWeek] : '';
                        $isWeekend= $dueObj && in_array($dueObj->dayOfWeek, [0,6]);
                        $dueFmt   = $dueObj ? $dueObj->format('d/m/Y') : '—';
                    @endphp
                    <div class="tl-item" style="{{ $sStatus==='PAID' ? 'opacity:.7;' : '' }}">
                        <div class="tl-dot {{ $dotClass }}">{{ $s['installment_number'] }}</div>
                        <div>
                            <div style="display:flex;align-items:center;gap:.4rem;flex-wrap:wrap;">
                                <span style="font-weight:700;font-size:.88rem;">
                                    Pago {{ $s['installment_number'] }} de {{ $loan['payments_count'] }}
                                </span>
                                @if($isWeekend)
                                    <span style="font-size:.70rem;background:rgba(245,138,0,.12);color:#b85e00;border:1px solid rgba(245,138,0,.25);border-radius:999px;padding:1px 6px;font-weight:700;">{{ $diaNom }} ⚠</span>
                                @endif
                            </div>
                            <div style="font-size:.82rem;color:var(--muted);margin-top:.05rem;">
                                <strong style="color:{{ $isWeekend ? '#b85e00' : 'var(--text-2)' }};">{{ $diaNom }}</strong>
                                {{ $dueFmt }}
                            </div>
                            <span class="badge {{ $badgeCls }}" style="font-size:.70rem;padding:2px 8px;margin-top:.2rem;">{{ $badgeLbl }}</span>
                        </div>
                        <div style="text-align:right;">
                            <div class="tl-amount">${{ number_format((float)($s['amount_due']??0),2) }}</div>
                            @if($sStatus==='PAID' && !empty($s['paid_at']))
                                <div style="font-size:.72rem;color:var(--muted);">
                                    {{ \Carbon\Carbon::parse($s['paid_at'])->setTimezone(config('app.timezone'))->format('d/m H:i') }}
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <p style="text-align:center;color:var(--muted);padding:.75rem 0;">Sin calendario generado.</p>
                @endforelse
            </div>
        </div>
    </div>

</div>
</div>
@endsection
