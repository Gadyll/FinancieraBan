@extends('layouts.app')

@section('title', 'Dashboard - MYBANK')

@section('content')
<div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-sub mb-0">
            Resumen del día y por cobrador.
        </p>
    </div>

    <form method="GET" action="{{ route('dashboard') }}" class="d-flex align-items-center gap-2">
        <input class="form-control" style="min-width: 220px" type="date" name="date" value="{{ $date }}">
        <button class="btn btn-primary" type="submit">Ver</button>
    </form>
</div>

@if(!empty($error))
    <div class="alert alert-danger mb-4">
        {{ $error }}
    </div>
@endif

<div class="row g-3 mb-4">
    <div class="col-12 col-md-6 col-lg-3">
        <div class="metric">
            <div class="metric-label">TOTAL COBRADO (DÍA)</div>
            <div class="metric-value">
                @if(is_array($daily) && isset($daily['total_paid']))
                    ${{ number_format((float)$daily['total_paid'], 2) }}
                @else
                    —
                @endif
            </div>
            <div class="metric-sub">{{ $date }}</div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-lg-3">
        <div class="metric">
            <div class="metric-label">NÚMERO DE PAGOS</div>
            <div class="metric-value">
                @if(is_array($daily) && isset($daily['payments_count']))
                    {{ (int)$daily['payments_count'] }}
                @else
                    —
                @endif
            </div>
            <div class="metric-sub">Pagos registrados</div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-lg-3">
        <div class="metric">
            <div class="metric-label">COBRADORES ACTIVOS</div>
            <div class="metric-value">{{ $activeCollectors ?? '—' }}</div>
            <div class="metric-sub">En operación</div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-lg-3">
        <div class="metric">
            <div class="metric-label">TICKETS (DÍA)</div>
            <div class="metric-value">
                @if(is_array($daily) && isset($daily['tickets_count']))
                    {{ (int)$daily['tickets_count'] }}
                @else
                    —
                @endif
            </div>
            <div class="metric-sub">Generados en el día</div>
        </div>
    </div>
</div>

<div class="surface surface-pad">
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h2 class="h4 mb-1">Resumen por cobrador</h2>
            <p class="page-sub mb-0">Quién cobró cuánto en la fecha seleccionada.</p>
        </div>
        
    </div>

    <div class="table-wrap">
        <table class="table table-clean table-striped table-hover">
            <thead>
                <tr>
                    <th>Cobrador</th>
                    <th class="text-end">Pagos</th>
                    <th class="text-end">Tickets</th>
                    <th class="text-end">Total cobrado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $it)
                    @php
                        $username = $it['username'] ?? ($it['user'] ?? '—');
                        $count = $it['payments_count'] ?? ($it['count'] ?? 0);
                        $tickets = $it['tickets_count'] ?? 0;
                        $amt = $it['total_paid'] ?? ($it['total'] ?? 0);
                    @endphp
                    <tr>
                        <td class="mono">{{ $username }}</td>
                        <td class="text-end">{{ (int)$count }}</td>
                        <td class="text-end">{{ (int)$tickets }}</td>
                        <td class="text-end">${{ number_format((float)$amt, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-4" style="color: rgba(234,241,255,.65)">
                            Sin datos para esta fecha
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection




