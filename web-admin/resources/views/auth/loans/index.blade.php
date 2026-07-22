@extends('layouts.app')
@section('title', 'Préstamos — MYBANK')

@push('styles')
<style>
.filter-bar {
    display: flex; gap: .65rem; align-items: center;
    flex-wrap: wrap; padding: 1rem 1.25rem;
    border-bottom: 1px solid rgba(26,111,207,.10);
    background: #fafcff;
}
.filter-bar .field-input {
    padding: .5rem .85rem; font-size: .88rem;
}
.status-tabs {
    display: flex; gap: .4rem; flex-wrap: wrap;
}
.stab {
    padding: .38rem .85rem; border-radius: 999px;
    font-size: .80rem; font-weight: 700; cursor: pointer;
    border: 1.5px solid transparent; transition: .13s;
    background: transparent; font-family: 'Outfit', sans-serif;
}
.stab:hover { border-color: var(--line); }
.stab.active-tab { background: var(--blue); color: #fff; border-color: var(--blue); }
.stab-all    { color: var(--text-2); }
.stab-active { color: var(--blue); border-color: rgba(26,111,207,.25); }
.stab-paid   { color: #0a8f6b; border-color: rgba(13,184,138,.28); }
.stab-late   { color: var(--red); border-color: rgba(224,58,58,.25); }

/* Amount cells */
.amount-cell { font-size: .97rem; font-weight: 800; font-variant-numeric: tabular-nums; }

/* Freq badge */
.freq {
    font-size: .76rem; font-weight: 700; padding: 3px 9px;
    border-radius: 999px; white-space: nowrap;
}
.freq-w { background: rgba(26,111,207,.08); color: var(--blue); border: 1px solid rgba(26,111,207,.18); }
.freq-b { background: rgba(124,58,237,.08); color: var(--purple); border: 1px solid rgba(124,58,237,.18); }
.freq-m { background: rgba(245,138,0,.08); color: #b85e00; border: 1px solid rgba(245,138,0,.18); }

/* Counter badge */
.cnt { min-width: 28px; display: inline-flex; align-items: center; justify-content: center; }
</style>
@endpush

@section('content')

{{-- Header --}}
<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <span class="breadcrumb-sep">›</span>
            Préstamos
        </div>
        <h1 class="page-title">Préstamos</h1>
        <p class="page-sub">{{ count($loans) }} crédito(s) registrado(s) en el sistema.</p>
    </div>
    <a href="{{ route('loans.create') }}" class="btn btn-primary btn-lg">
        <svg width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" d="M12 5v14M5 12h14"/>
        </svg>
        Nuevo préstamo
    </a>
</div>

@if(!empty($error))
    <div class="alert alert-danger">{{ $error }}</div>
@endif
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

{{-- Resumen rápido --}}
@php
    $totalActivos   = collect($loans)->where('status', 'ACTIVE')->count();
    $totalPagados   = collect($loans)->where('status', 'PAID')->count();
    $totalVencidos  = collect($loans)->where('status', 'LATE')->count();
    $montoTotal     = collect($loans)->sum(fn($l) => (float)($l['total_amount'] ?? 0));
@endphp
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:.75rem;margin-bottom:1.25rem;">
    <div class="card" style="padding:.9rem 1.1rem;border-top:3px solid var(--blue);">
        <div style="font-size:.74rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);">Total créditos</div>
        <div style="font-size:1.6rem;font-weight:900;color:var(--text);margin-top:.15rem;">{{ count($loans) }}</div>
    </div>
    <div class="card" style="padding:.9rem 1.1rem;border-top:3px solid var(--blue);">
        <div style="font-size:.74rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--blue);">Activos</div>
        <div style="font-size:1.6rem;font-weight:900;color:var(--blue);margin-top:.15rem;">{{ $totalActivos }}</div>
    </div>
    <div class="card" style="padding:.9rem 1.1rem;border-top:3px solid var(--red);">
        <div style="font-size:.74rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--red);">Vencidos</div>
        <div style="font-size:1.6rem;font-weight:900;color:var(--red);margin-top:.15rem;">{{ $totalVencidos }}</div>
    </div>
    <div class="card" style="padding:.9rem 1.1rem;border-top:3px solid var(--teal);">
        <div style="font-size:.74rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#0a8f6b;">Cartera total</div>
        <div style="font-size:1.35rem;font-weight:900;color:#0a8f6b;margin-top:.15rem;">${{ number_format($montoTotal, 0) }}</div>
    </div>
</div>

{{-- Tabla principal --}}
<div class="card">
    {{-- Filtros --}}
    <div class="filter-bar">
        <input class="field-input" id="searchInput" type="text"
               placeholder="🔍  Buscar cliente o #ID..." style="min-width:220px;flex:1;">
        <div class="status-tabs" id="statusTabs">
            <button class="stab stab-all active-tab" data-status="">Todos ({{ count($loans) }})</button>
            <button class="stab stab-active" data-status="ACTIVE">Activos ({{ $totalActivos }})</button>
            <button class="stab stab-late"   data-status="LATE">Vencidos ({{ $totalVencidos }})</button>
            <button class="stab stab-paid"   data-status="PAID">Pagados ({{ $totalPagados }})</button>
        </div>
    </div>

    <div class="card-body-flush">
        <div class="table-wrap">
            <table class="tbl" id="loansTable">
                <thead>
                    <tr>
                        <th style="width: 70px;">ID</th>
                        <th>Cliente</th>
                        <th style="text-align: center; width: 60px;">Ciclo</th>
                        <th class="cell-right">Capital</th>
                        <th class="cell-right">+ Interés</th>
                        <th class="cell-right">= Total</th>
                        <th class="cell-right">Cuota</th>
                        <th style="text-align: center; width: 70px;">Pagos</th>
                        <th style="width: 100px;">Frecuencia</th>
                        <th style="width: 110px;">Estado</th>
                        <th style="width: 100px;">Inicio</th>
                        <th style="width: 80px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loans as $loan)
                        @php
                            $status   = $loan['status'] ?? 'ACTIVE';
                            $rowClass = match($status){ 'ACTIVE'=>'row-active', 'PAID'=>'row-paid', 'LATE'=>'row-late', default=>'' };
                            $badge    = match($status){ 'ACTIVE'=>'badge-blue', 'PAID'=>'badge-teal', 'LATE'=>'badge-red', default=>'badge-gray' };
                            $label    = match($status){ 'ACTIVE'=>'Activo', 'PAID'=>'Pagado', 'LATE'=>'Vencido', default=>'Cancelado' };
                            $clientId = $loan['client_id'] ?? null;
                            $client   = $clientsMap[$clientId] ?? null;
                            $name     = $client['full_name'] ?? "Cliente #$clientId";
                            $num      = $client['client_number'] ?? '';
                            $interestAmt = (float)($loan['interest_amount'] ?? 0);
                            $freqVal  = $loan['frequency'] ?? '';
                            $freqLbl  = match($freqVal){ 'WEEKLY'=>'Semanal','BIWEEKLY'=>'Quincenal','MONTHLY'=>'Mensual',default=>$freqVal };
                            $freqCls  = match($freqVal){ 'WEEKLY'=>'freq-w','BIWEEKLY'=>'freq-b','MONTHLY'=>'freq-m',default=>'' };
                        @endphp
                        <tr class="{{ $rowClass }}" data-status="{{ $status }}"
                            data-name="{{ strtolower($name.' '.$num) }}">
                            <td class="mono cell-muted">#{{ $loan['id'] ?? '—' }}</td>
                            <td>
                                <div style="font-weight:700;">{{ $name }}</div>
                                @if($num)
                                    <div class="cell-muted" style="font-size:.80rem;">{{ $num }}</div>
                                @endif
                            </td>
                            <td style="text-align:center;">
                                <span class="badge badge-gray">C{{ $loan['cycle_number'] ?? '?' }}</span>
                            </td>
                            <td class="cell-right cell-muted">${{ number_format((float)($loan['principal_amount']??0),0) }}</td>
                            <td class="cell-right" style="color:var(--blue); font-weight:700;">${{ number_format($interestAmt,0) }}</td>
                            <td class="cell-right cell-money"><strong>${{ number_format((float)($loan['total_amount']??0),0) }}</strong></td>
                            <td class="cell-right cell-money text-teal">${{ number_format((float)($loan['payment_amount']??0),0) }}</td>
                            <td style="text-align:center;"><span class="badge badge-gray cnt">{{ $loan['payments_count']??'—' }}</span></td>
                            <td><span class="freq {{ $freqCls }}">{{ $freqLbl }}</span></td>
                            <td>
                                <span class="badge {{ $badge }}">
                                    <span class="badge-dot"></span>
                                    {{ $label }}
                                </span>
                            </td>
                            <td class="mono cell-muted" style="font-size:.82rem;">{{ $loan['start_date']??'—' }}</td>
                            <td>
                                <a href="{{ route('loans.show', ['loanId' => $loan['id']]) }}"
                                   class="btn btn-ghost btn-sm">
                                    Ver
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" style="text-align:center;padding:3rem;color:var(--muted);">
                                <svg width="36" height="36" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="display:block;margin:0 auto .75rem;opacity:.35;">
                                    <path stroke-linecap="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                No hay préstamos registrados.<br>
                                <a href="{{ route('loans.create') }}" class="btn btn-primary btn-sm" style="margin-top:.75rem;">Crear el primero</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
    var search  = document.getElementById('searchInput');
    var tabs    = document.querySelectorAll('#statusTabs .stab');
    var rows    = document.querySelectorAll('#loansTable tbody tr[data-name]');
    var current = '';

    function filter(){
        var q = (search.value||'').toLowerCase().trim();
        rows.forEach(function(row){
            var nameOk = !q || row.getAttribute('data-name').includes(q);
            var stOk   = !current || row.getAttribute('data-status') === current;
            row.style.display = (nameOk && stOk) ? '' : 'none';
        });
    }

    tabs.forEach(function(tab){
        tab.addEventListener('click', function(){
            tabs.forEach(function(t){ t.classList.remove('active-tab'); });
            tab.classList.add('active-tab');
            current = tab.getAttribute('data-status');
            filter();
        });
    });

    if(search) search.addEventListener('input', filter);
})();
</script>
@endpush
