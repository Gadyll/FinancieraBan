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

/* Collector table row hover */
.collector-row td { padding: .85rem 1rem; }
.collector-name { font-weight: 800; }
.collector-sub  { font-size: .82rem; color: #8896a8; }

/* Progress bar */
.prog-wrap { display: flex; align-items: center; gap: .6rem; }
.prog-bar  { flex: 1; height: 6px; background: rgba(26,111,207,.10); border-radius: 999px; overflow: hidden; }
.prog-fill { height: 100%; border-radius: 999px; transition: width .4s ease; }
.prog-label{ font-size: .84rem; font-weight: 700; min-width: 52px; text-align: right; }
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
            <p class="card-sub">Rendimiento del día — {{ $date }}</p>
        </div>
    </div>
    <div class="card-body-flush">
        <div class="table-wrap">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Cobrador</th>
                        <th class="text-right">Pagos</th>
                        <th class="text-right">Tickets</th>
                        <th>Total cobrado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $i => $it)
                        @php
                            $username = $it['username'] ?? ($it['user'] ?? '—');
                            $count    = (int)($it['payments_count'] ?? ($it['count'] ?? 0));
                            $tickets  = (int)($it['tickets_count'] ?? 0);
                            $amt      = (float)($it['total_paid'] ?? ($it['total'] ?? 0));
                            // Porcentaje relativo al total del día
                            $pct = $totalPaid > 0 ? min(100, round(($amt / $totalPaid) * 100)) : 0;
                        @endphp
                        <tr>
                            <td class="cell-muted">{{ $i + 1 }}</td>
                            <td>
                                <div class="collector-name">{{ $username }}</div>
                            </td>
                            <td class="text-right">
                                <span class="badge badge-blue">{{ $count }}</span>
                            </td>
                            <td class="text-right">
                                <span class="badge badge-purple">{{ $tickets }}</span>
                            </td>
                            <td style="min-width:200px;">
                                <div class="prog-wrap">
                                    <div class="prog-bar">
                                        <div class="prog-fill" style="width:{{ $pct }}%;background:var(--blue);"></div>
                                    </div>
                                    <span class="prog-label text-blue">${{ number_format($amt, 0) }}</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align:center;padding:2.5rem;color:#8896a8;">
                                <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="display:block;margin:0 auto .75rem;opacity:.4;">
                                    <path stroke-linecap="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                Sin movimientos para esta fecha.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
