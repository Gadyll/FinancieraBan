@extends('layouts.app')
@section('title', ($client['full_name'] ?? 'Cliente') . ' — MYBANK')

@push('styles')
<style>
.profile-hero {
    background: linear-gradient(135deg, var(--blue) 0%, var(--blue-dark) 100%);
    border-radius: 16px; padding: 1.75rem 2rem; color: #fff;
    display: flex; align-items: center; gap: 1.5rem;
    margin-bottom: 1.5rem; position: relative; overflow: hidden;
}
.profile-hero::before { content:''; position:absolute; top:-40px; right:-40px; width:180px; height:180px; border-radius:50%; background:rgba(255,255,255,.06); }
.profile-hero::after  { content:''; position:absolute; bottom:-60px; right:80px; width:220px; height:220px; border-radius:50%; background:rgba(255,255,255,.04); }
.profile-avatar { width:72px; height:72px; border-radius:50%; background:rgba(255,255,255,.18); display:flex; align-items:center; justify-content:center; font-size:2rem; font-weight:900; flex-shrink:0; border:3px solid rgba(255,255,255,.35); z-index:1; }
.profile-info { flex:1; z-index:1; }
.profile-name { font-size:1.6rem; font-weight:900; margin:0 0 .25rem; }
.profile-num  { font-size:.90rem; opacity:.75; font-weight:600; margin-bottom:.5rem; }
.profile-chips { display:flex; gap:.5rem; flex-wrap:wrap; }
.profile-chip { background:rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.3); border-radius:999px; padding:.25rem .75rem; font-size:.80rem; font-weight:700; display:flex; align-items:center; gap:.4rem; }
.info-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:1.25rem; margin-bottom:1.5rem; }
@media(max-width:860px){ .info-grid{ grid-template-columns:1fr; } }
.kv { display:grid; grid-template-columns:140px 1fr; gap:.4rem .75rem; font-size:.91rem; }
.kv .k { color:var(--muted); font-weight:700; }
.kv .v { font-weight:600; color:var(--text); word-break:break-word; }
.loan-status-card { border-radius:14px; overflow:hidden; margin-bottom:1.5rem; border:1.5px solid rgba(26,111,207,.18); }
.loan-status-head { background:linear-gradient(135deg,rgba(26,111,207,.07),rgba(13,184,138,.05)); padding:.9rem 1.25rem; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid rgba(26,111,207,.10); flex-wrap:wrap; gap:.5rem; }
.loan-status-body { background:#fff; padding:1.25rem; }
.prog-track { height:12px; border-radius:999px; background:rgba(26,111,207,.10); overflow:hidden; margin:.75rem 0; }
.prog-fill { height:100%; border-radius:999px; background:linear-gradient(90deg,var(--teal),#0a8f6b); transition:width .5s; }
.prog-fill.danger { background:linear-gradient(90deg,var(--red),#c72a2a); }
.stat-row { display:grid; grid-template-columns:repeat(3,1fr); gap:.75rem; margin-top:.9rem; }
@media(max-width:600px){ .stat-row{ grid-template-columns:1fr; } }
.stat-box { border-radius:10px; padding:.8rem 1rem; border:1.5px solid rgba(26,111,207,.12); background:#fafcff; text-align:center; }
.stat-lbl { font-size:.73rem; font-weight:800; text-transform:uppercase; letter-spacing:.07em; color:var(--muted); }
.stat-val { font-size:1.35rem; font-weight:900; margin:.2rem 0; font-variant-numeric:tabular-nums; }
.stat-sub { font-size:.78rem; color:var(--muted); }
.history-row td { padding:.8rem 1rem; vertical-align:middle; }
.cy-badge { width:26px; height:26px; border-radius:50%; background:rgba(26,111,207,.12); color:var(--blue); display:inline-flex; align-items:center; justify-content:center; font-size:.72rem; font-weight:900; }
.loan-num { font-weight:800; font-size:.88rem; font-family:'Courier New',monospace; color:var(--blue); }
.no-loan-msg { text-align:center; padding:2.5rem 1.5rem; color:var(--muted); font-weight:600; font-size:.95rem; }
</style>
@endpush

@section('content')
@php
    $fullName   = $client['full_name']     ?? '—';
    $clientId   = $client['id']            ?? null;
    $clientNum  = $client['client_number'] ?? '—';
    $phone      = $client['phone']         ?? '—';
    $address    = $client['address']       ?? '—';
    $marital    = $client['marital_status'] ?? '—';
    $spouse     = $client['spouse_full_name'] ?? null;
    $birthDate  = $client['birth_date']    ?? null;
    $occupation = $client['occupation']    ?? null;
    $income     = $client['monthly_income'] ?? null;
    $initials   = mb_strtoupper(mb_substr($fullName,0,1));
    $gName    = $client['guarantor_full_name']     ?? '—';
    $gAddr    = $client['guarantor_address']        ?? '—';
    $gPhone   = $client['guarantor_phone']          ?? '—';
    $gMarital = $client['guarantor_marital_status'] ?? '—';
    $hasLoan    = !is_null($activeLoan);
    $lStatus    = $activeLoan['status'] ?? '';
    $lStatusCls = match($lStatus){ 'ACTIVE'=>'badge-blue','PAID'=>'badge-teal','LATE'=>'badge-red',default=>'badge-gray' };
    $lStatusLbl = match($lStatus){ 'ACTIVE'=>'Activo','PAID'=>'Pagado','LATE'=>'Vencido',default=>'Cancelado' };
    $principal  = (float)($activeLoan['principal_amount'] ?? 0);
    $totalAmt   = (float)($activeLoan['total_amount']     ?? 0);
    $cuota      = (float)($activeLoan['payment_amount']   ?? 0);
    $nPagos     = (int)($activeLoan['payments_count']     ?? 0);
    $loanRate   = (float)($activeLoan['interest_rate']    ?? 0);
    $totalPaid  = (float)($activeSummary['total_paid']            ?? 0);
    $remaining  = (float)($activeSummary['remaining_balance']     ?? max(0,$totalAmt-$totalPaid));
    $overdue    = (int)($activeSummary['overdue_installments']    ?? 0);
    $paidPct    = $totalAmt>0 ? min(100,round(($totalPaid/$totalAmt)*100,1)) : 0;
    $freqLbl    = match($activeLoan['frequency']??''){ 'WEEKLY'=>'Semanal','BIWEEKLY'=>'Quincenal','MONTHLY'=>'Mensual','YEARLY'=>'Anual',default=>($activeLoan['frequency']??'—') };
@endphp

<div class="page-header" style="margin-bottom:1.25rem;">
    <div>
        <div class="breadcrumb">
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <span class="breadcrumb-sep">›</span>
            <a href="{{ route('clients.index') }}">Clientes</a>
            <span class="breadcrumb-sep">›</span>
            {{ $fullName }}
        </div>
        <h1 class="page-title">Perfil del Cliente</h1>
    </div>
    <div style="display:flex;gap:.65rem;flex-wrap:wrap;">
        @if($hasLoan)
            <a href="{{ route('loans.show',['loanId'=>$activeLoan['id']]) }}" class="btn btn-primary">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Ver préstamo activo
            </a>
        @else
            <a href="{{ route('loans.create',['client_id'=>$clientId]) }}" class="btn btn-primary">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                Nuevo préstamo
            </a>
        @endif
        <a href="{{ route('clients.index') }}" class="btn btn-ghost">← Regresar</a>
    </div>
</div>

<div class="profile-hero">
    <div class="profile-avatar">{{ $initials }}</div>
    <div class="profile-info">
        <div class="profile-name">{{ $fullName }}</div>
        <div class="profile-num">{{ $clientNum }}</div>
        <div class="profile-chips">
            <span class="profile-chip">{{ $phone }}</span>
            <span class="profile-chip">{{ $marital }}</span>
            @if($hasLoan)
                <span class="profile-chip" style="background:rgba(13,184,138,.25);border-color:rgba(13,184,138,.5);">✓ Préstamo {{ $lStatusLbl }}</span>
            @else
                <span class="profile-chip">Sin préstamo activo</span>
            @endif
        </div>
    </div>
</div>

<div class="info-grid">
    <div class="card">
        <div class="card-head card-accent-blue">
            <h2 class="card-title">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                Datos Personales
            </h2>
        </div>
        <div class="card-body">
            <div class="kv">
                <span class="k">Nombre</span><span class="v">{{ $fullName }}</span>
                <span class="k">Teléfono</span><span class="v">{{ $phone }}</span>
                <span class="k">Dirección</span><span class="v">{{ $address }}</span>
                <span class="k">Estado civil</span><span class="v">{{ $marital }}</span>
                @if($spouse)<span class="k">Cónyuge</span><span class="v">{{ $spouse }}</span>@endif
                @if($birthDate)
                    <span class="k">Nacimiento</span>
                    <span class="v">{{ \Carbon\Carbon::parse($birthDate)->locale('es')->isoFormat('D [de] MMMM YYYY') }}</span>
                @endif
                @if($occupation)<span class="k">Ocupación</span><span class="v">{{ $occupation }}</span>@endif
                @if($income !== null && $income > 0)
                    <span class="k">Ingreso mensual</span><span class="v">${{ number_format($income,2) }}</span>
                @endif
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-head" style="background:linear-gradient(135deg,rgba(245,138,0,.08),rgba(245,138,0,.04));border-bottom:2px solid rgba(245,138,0,.18);">
            <h2 class="card-title" style="color:var(--orange);">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                Datos del Aval
            </h2>
        </div>
        <div class="card-body">
            <div class="kv">
                <span class="k">Nombre</span><span class="v">{{ $gName }}</span>
                <span class="k">Teléfono</span><span class="v">{{ $gPhone }}</span>
                <span class="k">Dirección</span><span class="v">{{ $gAddr }}</span>
                <span class="k">Estado civil</span><span class="v">{{ $gMarital }}</span>
            </div>
        </div>
    </div>
</div>

@if($hasLoan)
<div class="loan-status-card">
    <div class="loan-status-head">
        <div style="display:flex;align-items:center;gap:.65rem;font-weight:800;font-size:.97rem;">
            <svg width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Préstamo Activo — Ciclo #{{ $activeLoan['cycle_number'] ?? '—' }}
        </div>
        <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;">
            <span class="badge {{ $lStatusCls }}">{{ $lStatusLbl }}</span>
            @if($overdue > 0)<span class="badge badge-red">⚠ {{ $overdue }} vencido(s)</span>@endif
            <a href="{{ route('loans.show',['loanId'=>$activeLoan['id']]) }}" class="btn btn-primary" style="padding:.45rem .9rem;font-size:.85rem;">Ver detalle →</a>
        </div>
    </div>
    <div class="loan-status-body">
        <div style="display:flex;justify-content:space-between;font-size:.84rem;font-weight:700;">
            <span style="color:var(--muted);">Pagado: <span style="color:var(--teal);">{{ number_format($paidPct,1) }}%</span></span>
            <span style="color:var(--muted);">Restante: <strong>${{ number_format($remaining,2) }}</strong></span>
        </div>
        <div class="prog-track"><div class="prog-fill {{ $overdue>0?'danger':'' }}" style="width:{{ $paidPct }}%;"></div></div>
        <div class="stat-row">
            <div class="stat-box">
                <div class="stat-lbl">Capital</div>
                <div class="stat-val" style="color:var(--blue);">${{ number_format($principal,0) }}</div>
                <div class="stat-sub">Monto entregado</div>
            </div>
            <div class="stat-box">
                <div class="stat-lbl">Total a pagar</div>
                <div class="stat-val">${{ number_format($totalAmt,0) }}</div>
                <div class="stat-sub">{{ $nPagos }} pagos · {{ $freqLbl }}</div>
            </div>
            <div class="stat-box">
                <div class="stat-lbl">Cuota</div>
                <div class="stat-val" style="color:#0a8f6b;">${{ number_format($cuota,2) }}</div>
                <div class="stat-sub">Tasa {{ $loanRate }}%</div>
            </div>
        </div>
    </div>
</div>
@else
<div class="card mb-3">
    <div class="card-body no-loan-msg">
        <svg width="42" height="42" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="color:var(--muted);display:block;margin:0 auto .75rem;"><path stroke-linecap="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
        Este cliente no tiene préstamo activo.
        <br>
        <a href="{{ route('loans.create',['client_id'=>$clientId]) }}" class="btn btn-primary" style="margin-top:.85rem;display:inline-flex;">+ Crear préstamo</a>
    </div>
</div>
@endif

<div class="card">
    <div class="card-head card-accent-blue">
        <h2 class="card-title">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            Historial de Préstamos ({{ count($allLoans) }})
        </h2>
    </div>
    @if(count($allLoans) > 0)
    <div class="tbl-wrap">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Ciclo</th><th>Capital</th><th>Total</th><th>Cuota</th><th>Frecuencia</th><th>Inicio</th><th>Estado</th><th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($allLoans as $l)
                @php
                    $ls=$l['status']??'';
                    $lsCls=match($ls){'ACTIVE'=>'badge-blue','PAID'=>'badge-teal','LATE'=>'badge-red',default=>'badge-gray'};
                    $lsLbl=match($ls){'ACTIVE'=>'Activo','PAID'=>'Pagado','LATE'=>'Vencido',default=>'Cancelado'};
                    $lFreq=match($l['frequency']??''){'WEEKLY'=>'Semanal','BIWEEKLY'=>'Quincenal','MONTHLY'=>'Mensual','YEARLY'=>'Anual',default=>($l['frequency']??'—')};
                @endphp
                <tr class="history-row">
                    <td><span class="cy-badge">{{ $l['cycle_number']??'—' }}</span></td>
                    <td><span class="loan-num">${{ number_format($l['principal_amount']??0,0) }}</span></td>
                    <td style="font-weight:700;">${{ number_format($l['total_amount']??0,2) }}</td>
                    <td style="font-weight:700;color:var(--teal);">${{ number_format($l['payment_amount']??0,2) }}</td>
                    <td>
                        <span style="font-size:.83rem;font-weight:600;color:var(--muted);">{{ $lFreq }}</span>
                        <div style="font-size:.78rem;color:var(--muted);">{{ $l['payments_count']??'—' }} pagos</div>
                    </td>
                    <td style="font-size:.85rem;color:var(--muted);font-weight:600;white-space:nowrap;">
                        {{ $l['start_date'] ? \Carbon\Carbon::parse($l['start_date'])->format('d/m/Y') : '—' }}
                    </td>
                    <td><span class="badge {{ $lsCls }}">{{ $lsLbl }}</span></td>
                    <td><a href="{{ route('loans.show',['loanId'=>$l['id']]) }}" class="btn btn-ghost" style="padding:.35rem .7rem;font-size:.83rem;">Ver →</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="card-body no-loan-msg">Sin historial de préstamos registrado.</div>
    @endif
</div>
@endsection

