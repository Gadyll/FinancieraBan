@extends('layouts.app')
@section('title', 'Historial de Auditoría — MYBANK Admin')

@section('content')
<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <span class="breadcrumb-sep">›</span>
            Auditoría
        </div>
        <h1 class="page-title">
            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="display:inline-block;vertical-align:middle;margin-right:.45rem;margin-top:-.15rem;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Historial de Auditoría
        </h1>
        <p class="page-sub">Registro transaccional de todas las acciones realizadas en el sistema.</p>
    </div>
    <div>
        <span style="background:var(--orange-lt);border:1px solid rgba(245,138,0,.3);color:var(--orange);border-radius:999px;padding:.3rem .8rem;font-size:.82rem;font-weight:700;">
            {{ $logs->total() }} registros
        </span>
    </div>
</div>

{{-- Filtros --}}
<form method="GET" action="{{ route('audit.index') }}" class="card" style="padding:1.25rem;margin-bottom:1.5rem;">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:.85rem;align-items:end;">
        <div>
            <label class="form-label">Módulo</label>
            <select name="module" id="filter-module" class="form-select">
                <option value="">Todos</option>
                @foreach($modules as $m)
                    <option value="{{ $m }}" {{ request('module') === $m ? 'selected' : '' }}>{{ ucfirst($m) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label">Acción</label>
            <select name="action" id="filter-action" class="form-select">
                <option value="">Todas</option>
                @foreach($actions as $a)
                    <option value="{{ $a }}" {{ request('action') === $a ? 'selected' : '' }}>{{ $a }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label">Usuario</label>
            <input type="text" name="username" id="filter-user" class="form-control" placeholder="Nombre de usuario" value="{{ request('username') }}">
        </div>
        <div>
            <label class="form-label">Desde</label>
            <input type="date" name="from" id="filter-from" class="form-control" value="{{ request('from') }}">
        </div>
        <div>
            <label class="form-label">Hasta</label>
            <input type="date" name="to" id="filter-to" class="form-control" value="{{ request('to') }}">
        </div>
        <div style="display:flex;gap:.5rem;">
            <button type="submit" class="btn-primary" id="btn-filter-audit" style="flex:1;">Filtrar</button>
            <a href="{{ route('audit.index') }}" class="btn-ghost" id="btn-clear-audit" style="padding:.5rem .75rem;">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </a>
        </div>
    </div>
</form>

{{-- Tabla --}}
<div class="card w-full max-w-full" style="overflow:hidden;">
    @if($logs->isEmpty())
        <div style="padding:3rem;text-align:center;color:var(--muted);">
            <svg width="40" height="40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 1rem;display:block;opacity:.4;">
                <path stroke-linecap="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            No hay registros de auditoría aún.
        </div>
    @else
        <div class="overflow-x-auto table-responsive w-full max-w-full">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Fecha / Hora</th>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Módulo</th>
                        <th>Acción</th>
                        <th>Descripción</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                    <tr>
                        <td style="white-space:nowrap;font-size:.84rem;color:var(--muted);">
                            {{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i:s') }}
                        </td>
                        <td>
                            <span style="font-weight:700;">{{ $log->username ?? '—' }}</span>
                        </td>
                        <td>
                            @if($log->user_role === 'ADMIN')
                                <span style="background:rgba(245,158,11,.18);border:1px solid rgba(245,158,11,.4);color:#f59e0b;border-radius:999px;padding:.2rem .55rem;font-size:.78rem;font-weight:700;">
                                    ADMIN
                                </span>
                            @else
                                <span style="background:rgba(13,184,138,.18);border:1px solid rgba(13,184,138,.35);color:#0db88a;border-radius:999px;padding:.2rem .55rem;font-size:.78rem;font-weight:700;">
                                    USER
                                </span>
                            @endif
                        </td>
                        <td>
                            <span class="badge-module badge-module--{{ $log->module }}">
                                {{ ucfirst($log->module) }}
                            </span>
                        </td>
                        <td>
                            <code style="font-size:.82rem;background:var(--bg);padding:.15rem .4rem;border-radius:5px;">{{ $log->action }}</code>
                        </td>
                        <td style="max-width:320px;font-size:.88rem;" class="break-words">
                            {{ $log->description ?? '—' }}
                        </td>
                        <td style="font-size:.82rem;color:var(--muted);">
                            {{ $log->ip_address ?? '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Paginación --}}
        @if($logs->hasPages())
        <div style="padding:1rem 1.25rem;border-top:1px solid var(--line);display:flex;justify-content:space-between;align-items:center;font-size:.86rem;color:var(--muted);">
            <span>Mostrando {{ $logs->firstItem() }}–{{ $logs->lastItem() }} de {{ $logs->total() }}</span>
            <div style="display:flex;gap:.5rem;">
                @if($logs->onFirstPage())
                    <span style="opacity:.4;padding:.3rem .75rem;border:1px solid var(--line);border-radius:8px;">Anterior</span>
                @else
                    <a href="{{ $logs->previousPageUrl() }}" id="audit-prev-page" style="padding:.3rem .75rem;border:1px solid var(--line);border-radius:8px;color:var(--blue);">Anterior</a>
                @endif
                @if($logs->hasMorePages())
                    <a href="{{ $logs->nextPageUrl() }}" id="audit-next-page" style="padding:.3rem .75rem;border:1px solid var(--line);border-radius:8px;color:var(--blue);">Siguiente</a>
                @else
                    <span style="opacity:.4;padding:.3rem .75rem;border:1px solid var(--line);border-radius:8px;">Siguiente</span>
                @endif
            </div>
        </div>
        @endif
    @endif
</div>
@endsection

@push('styles')
<style>
.badge-module {
    display:inline-block;
    padding:.2rem .6rem;
    border-radius:7px;
    font-size:.78rem;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:.03em;
}
.badge-module--clients   { background:rgba(26,111,207,.14);  color:#1a6fcf; }
.badge-module--loans     { background:rgba(13,184,138,.14);  color:#0db88a; }
.badge-module--payments  { background:rgba(124,58,237,.14);  color:#7c3aed; }
.badge-module--users     { background:rgba(245,138,0,.14);   color:#f58a00; }
</style>
@endpush
