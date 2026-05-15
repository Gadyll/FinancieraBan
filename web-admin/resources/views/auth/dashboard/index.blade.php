@extends('layouts.app')
@section('title', 'Dashboard — MYBANK')

@push('styles')
<style>
.summary-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}
@media (max-width: 900px) { .summary-grid { grid-template-columns: 1fr 1fr; } }
@media (max-width: 520px) { .summary-grid { grid-template-columns: 1fr; } }

/* Quick actions */
.quick-links {
    display: flex; gap: .6rem; flex-wrap: wrap; margin-bottom: 1.5rem;
}

/* Progress bar — already in app.css, override here for dashboard */
.collector-row td { padding: .85rem 1rem; vertical-align: middle; }
.collector-name { font-weight: 800; font-size: .95rem; }
.collector-sub  { font-size: .82rem; color: #8896a8; }
</style>
@endpush

@section('content')
@php
    $totalPaid     = is_array($daily) ? (float)($daily['total_paid'] ?? 0) : 0;
    $paymentsCount = is_array($daily) ? (int)($daily['payments_count'] ?? 0) : 0;
    $ticketsCount  = is_array($daily) ? (int)($daily['tickets_count'] ?? 0) : 0;
    $collectors    = (int)($activeCollectors ?? 0);
@endphp

{{-- Header --}}
<div class="page-header">
    <div>
        <div class="breadcrumb">
            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
            </svg>
            Panel principal
        </div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-sub">Resumen de operaciones — {{ \Carbon\Carbon::parse($date)->locale('es')->isoFormat('dddd, D [de] MMMM YYYY') }}</p>
    </div>
    <form method="GET" action="{{ route('dashboard') }}" style="display:flex;gap:.5rem;align-items:center;">
        <input class="field-input" style="width:180px;padding:.55rem .85rem;" type="date" name="date" value="{{ $date }}">
        <button class="btn btn-ghost" type="submit">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/>
            </svg>
            Ver
        </button>
    </form>
</div>

@if(!empty($error))
    <div class="alert alert-danger">
        <div class="alert-icon">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
        </div>
        {{ $error }}
    </div>
@endif

{{-- ── Métricas ── --}}
<div class="summary-grid">

    <div class="metric-card blue">
        <div class="metric-icon blue">
            <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <div class="metric-label">Total cobrado</div>
            <div class="metric-value">${{ number_format($totalPaid, 0) }}</div>
            <div class="metric-sub">del día {{ $date }}</div>
        </div>
    </div>

    <div class="metric-card teal">
        <div class="metric-icon teal">
            <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
        </div>
        <div>
            <div class="metric-label">Pagos registrados</div>
            <div class="metric-value">{{ $paymentsCount }}</div>
            <div class="metric-sub">transacciones del día</div>
        </div>
    </div>

    <div class="metric-card orange">
        <div class="metric-icon orange">
            <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/>
            </svg>
        </div>
        <div>
            <div class="metric-label">Cobradores activos</div>
            <div class="metric-value">{{ $collectors }}</div>
            <div class="metric-sub">en operación hoy</div>
        </div>
    </div>

    <div class="metric-card purple">
        <div class="metric-icon purple">
            <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
            </svg>
        </div>
        <div>
            <div class="metric-label">Tickets generados</div>
            <div class="metric-value">{{ $ticketsCount }}</div>
            <div class="metric-sub">recibos del día</div>
        </div>
    </div>

</div>

{{-- ── Accesos rápidos ── --}}
<div class="quick-links mb-3">
    <a href="{{ route('clients.index') }}" class="btn btn-ghost">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" d="M12 5v14M5 12h14"/>
        </svg>
        Nuevo cliente
    </a>
    <a href="{{ route('loans.create') }}" class="btn btn-primary">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" d="M12 5v14M5 12h14"/>
        </svg>
        Nuevo préstamo
    </a>
    <a href="{{ route('loans.index') }}" class="btn btn-ghost">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        Ver préstamos
    </a>
</div>

{{-- ── Tabla de cobradores ── --}}
<div class="card">
    <div class="card-head card-accent-blue">
        <div>
            <h2 class="card-title">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Resumen por cobrador
            </h2>
            <p class="card-sub">Rendimiento del día — {{ $date }} · Clic en una fila para ver el historial</p>
        </div>
    </div>
    <div class="card-body-flush">
        @forelse($items as $i => $it)
            @php
                $uid       = $it['user_id'] ?? 0;
                $username  = $it['username'] ?? '—';
                $count     = (int)($it['payments_count'] ?? 0);
                $tickets   = (int)($it['tickets_count']  ?? 0);
                $amt       = (float)($it['total_paid']   ?? 0);
                $pct       = $totalPaid > 0 ? min(100, round(($amt / $totalPaid) * 100)) : 0;
                $detail    = $it['payments_detail'] ?? [];
                $rowId     = "collector-detail-{$uid}";
                $hasActivity = $count > 0;
                $methodMap = ['CASH'=>'Efectivo','TRANSFER'=>'Transferencia','CARD'=>'Tarjeta','OTHER'=>'Otro'];
            @endphp

            {{-- ── Fila resumen (clickeable) ── --}}
            <div onclick="toggleDetail('{{ $rowId }}')"
                 id="row-{{ $uid }}"
                 style="display:flex; align-items:center; flex-wrap:wrap; gap:.75rem;
                        padding: 1rem 1.25rem; cursor:pointer;
                        border-bottom: 1px solid rgba(26,111,207,.07);
                        transition:.15s; user-select:none;
                        {{ !$hasActivity ? 'opacity:.6;' : '' }}"
                 onmouseover="this.style.background='rgba(26,111,207,.03)'"
                 onmouseout="this.style.background='transparent'">

                {{-- # --}}
                <div style="width:28px;color:#8896a8;font-size:.84rem;flex-shrink:0;">{{ $i+1 }}</div>

                {{-- Nombre + sub --}}
                <div style="flex:1;min-width:120px;">
                    <div style="font-weight:800;font-size:.96rem;">{{ $username }}</div>
                    <div style="font-size:.76rem;color:#8896a8;">
                        @if($hasActivity)
                            {{ $count }} cobro(s) de clientes asignados · {{ $tickets }} ticket(s)
                        @else
                            <span style="color:#c5cdd8;">Sin actividad este día</span>
                        @endif
                    </div>
                </div>

                {{-- Badges: grises si 0 --}}
                <div style="display:flex;gap:.5rem;align-items:center;flex-shrink:0;">
                    <span class="badge {{ $hasActivity ? 'badge-blue' : 'badge-gray' }} badge-num">
                        {{ $count }}
                    </span>
                    <span class="badge {{ $tickets > 0 ? 'badge-purple' : 'badge-gray' }} badge-num">
                        {{ $tickets }}
                    </span>
                </div>

                {{-- Barra / estado --}}
                <div style="min-width:180px;flex:1;max-width:280px;">
                    @if($hasActivity)
                        <div class="prog-wrap">
                            <div class="prog-bar">
                                <div class="prog-fill" style="width:{{ $pct }}%;background:var(--blue);"></div>
                            </div>
                            <span class="prog-label text-blue" style="min-width:80px;">${{ number_format($amt,0) }}</span>
                        </div>
                    @else
                        <div style="font-size:.80rem;color:#c5cdd8;font-style:italic;">$0 — sin cobros</div>
                    @endif
                </div>

                {{-- Flecha --}}
                <div id="arrow-{{ $uid }}" style="flex-shrink:0;color:#8896a8;transition:.25s;transform:rotate(180deg);">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
            </div>

            {{-- ── Detalle de pagos (expandido por defecto) ── --}}
            <div id="{{ $rowId }}"
                 style="display:block; background:rgba(26,111,207,.025);
                        border-bottom: 1px solid rgba(26,111,207,.10);">
                @if(count($detail) > 0)
                    <div style="padding:.5rem 1.25rem 1rem;">
                        <div style="font-size:.74rem;font-weight:800;text-transform:uppercase;
                                    letter-spacing:.09em;color:var(--blue);margin:.6rem 0 .5rem;">
                            Historial de pagos del día
                        </div>
                        <div class="table-wrap" style="border-radius:10px;overflow:hidden;border:1px solid rgba(26,111,207,.12);">
                            <table class="tbl" style="font-size:.85rem;">
                                <thead>
                                    <tr style="background:rgba(26,111,207,.06);">
                                        <th style="width:36px;">#</th>
                                        <th>Cliente</th>
                                        <th>N° Cliente</th>
                                        <th>Ticket</th>
                                        <th class="text-right">Monto</th>
                                        <th>Método</th>
                                        <th>Hora</th>
                                        <th>Capturado por</th>
                                        <th style="width:44px;text-align:center;">🖨️</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($detail as $di => $pay)
                                        <tr>
                                            <td class="cell-muted">{{ $di + 1 }}</td>
                                            <td style="font-weight:700;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                                {{ $pay['client_name'] ?? '—' }}
                                            </td>
                                            <td>
                                                <span class="badge badge-gray" style="font-size:.75rem;">
                                                    {{ $pay['client_number'] ?? '—' }}
                                                </span>
                                            </td>
                                            <td class="mono cell-muted" style="font-size:.80rem;">
                                                {{ $pay['ticket_number'] ?? '—' }}
                                            </td>
                                            <td class="text-right">
                                                <strong style="color:var(--teal);">
                                                    ${{ number_format((float)($pay['amount_paid']??0), 2) }}
                                                </strong>
                                            </td>
                                            <td>
                                                <span class="badge badge-gray" style="font-size:.73rem;">
                                                    {{ $methodMap[$pay['payment_method']??''] ?? ($pay['payment_method']??'—') }}
                                                </span>
                                            </td>
                                            <td class="mono cell-muted" style="font-size:.80rem;">
                                                @php $isoTs = $pay['paid_at_iso'] ?? null; @endphp
                                                @if($isoTs)
                                                    <span class="local-time" data-utc="{{ $isoTs }}"
                                                          title="{{ $isoTs }}">
                                                        {{ $pay['paid_at'] ?? '—' }}
                                                    </span>
                                                @else
                                                    {{ $pay['paid_at'] ?? '—' }}
                                                @endif
                                            </td>
                                            <td style="font-size:.78rem;">
                                                @php $regBy = $pay['registered_by'] ?? '—'; @endphp
                                                @if($regBy === $username)
                                                    <span style="color:#8896a8;">{{ $regBy }}</span>
                                                @else
                                                    <span class="badge badge-orange" style="font-size:.70rem;" title="Registrado por admin">
                                                        {{ $regBy }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td style="text-align:center;">
                                                @if(!empty($pay['loan_id']))
                                                    <a href="{{ route('loans.ticket', ['loanId'=>$pay['loan_id']]) }}?payment_id={{ $pay['payment_id']??'' }}"
                                                       target="_blank"
                                                       title="Imprimir ticket"
                                                       style="display:inline-flex;align-items:center;justify-content:center;
                                                              width:28px;height:28px;border-radius:7px;
                                                              background:rgba(26,111,207,.08);color:var(--blue);
                                                              text-decoration:none;transition:.15s;"
                                                       onmouseover="this.style.background='rgba(26,111,207,.2)'"
                                                       onmouseout="this.style.background='rgba(26,111,207,.08)'">
                                                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
                                                            <rect x="6" y="14" width="12" height="8" rx="1"/>
                                                        </svg>
                                                    </a>
                                                @else
                                                    <span style="color:#ccc;">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr style="background:rgba(13,184,138,.05);border-top:2px solid rgba(13,184,138,.20);">
                                        <td colspan="5" style="font-weight:800;padding:.7rem 1rem;font-size:.84rem;">
                                            TOTAL DEL DÍA — {{ strtoupper($username) }}
                                        </td>
                                        <td class="text-right" style="font-weight:900;color:var(--teal);font-size:1rem;padding:.7rem 1rem;">
                                            ${{ number_format($amt, 2) }}
                                        </td>
                                        <td colspan="3"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @else
                    <div style="padding:1rem 1.5rem;color:#8896a8;font-size:.87rem;font-style:italic;">
                        Sin detalle de pagos para esta fecha.
                    </div>
                @endif
            </div>

        @empty
            <div style="text-align:center;padding:2.5rem;color:#8896a8;">
                <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="display:block;margin:0 auto .75rem;opacity:.4;">
                    <path stroke-linecap="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Sin movimientos para esta fecha.
            </div>
        @endforelse
    </div>
</div>

<script>
function toggleDetail(id) {
    const el    = document.getElementById(id);
    const uid   = id.replace('collector-detail-', '');
    const arrow = document.getElementById('arrow-' + uid);
    const open  = el.style.display !== 'none';

    el.style.display = open ? 'none' : 'block';
    if (arrow) arrow.style.transform = open ? 'rotate(0deg)' : 'rotate(180deg)';
}

// Convierte timestamps UTC/ISO a la hora LOCAL del dispositivo
function applyLocalTimes() {
    document.querySelectorAll('.local-time[data-utc]').forEach(function(el) {
        const iso = el.getAttribute('data-utc');
        if (!iso) return;
        try {
            const d = new Date(iso);
            if (isNaN(d.getTime())) return;
            el.textContent = d.toLocaleTimeString([], {
                hour:   '2-digit',
                minute: '2-digit',
                hour12: false
            });
            // Tooltip con fecha y hora completa local
            el.title = d.toLocaleString();
        } catch(e) {}
    });
}
document.addEventListener('DOMContentLoaded', applyLocalTimes);
</script>
@endsection
