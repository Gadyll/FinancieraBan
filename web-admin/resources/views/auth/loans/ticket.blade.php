<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ticket {{ $ticket['ticket_number'] ?? '' }} — MYBANK</title>
<style>
/* ═══════════════════════════════════════════
   RESET GLOBAL
═══════════════════════════════════════════ */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

/* ═══════════════════════════════════════════
   PANTALLA — vista previa del ticket
═══════════════════════════════════════════ */
@media screen {
    body {
        background: #0d1b2e;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 1.5rem;
        font-family: 'Segoe UI', system-ui, sans-serif;
        padding: 2rem 1rem;
    }

    .page-title {
        color: rgba(255,255,255,.6);
        font-size: .82rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .15em;
    }

    /* Contenedor del ticket en pantalla */
    .ticket-wrapper {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 20px 60px rgba(0,0,0,.50), 0 0 0 1px rgba(255,255,255,.05);
        overflow: hidden;
        width: 216px;        /* 57mm ≈ 216px a 96dpi */
        position: relative;
    }

    /* Perforación superior */
    .ticket-wrapper::before {
        content: '';
        display: block;
        height: 12px;
        background: repeating-linear-gradient(90deg,
            #0d1b2e 0 6px, transparent 6px 12px);
        opacity: .18;
    }

    /* Botones de acción */
    .actions {
        display: flex;
        gap: .65rem;
        flex-wrap: wrap;
        justify-content: center;
    }

    .btn-print {
        display: inline-flex; align-items: center; gap: .45rem;
        padding: .75rem 1.75rem;
        background: #1a6fcf; color: #fff;
        border: none; border-radius: 10px;
        font-size: .92rem; font-weight: 800;
        cursor: pointer; letter-spacing: .02em;
        box-shadow: 0 4px 16px rgba(26,111,207,.40);
        transition: .15s;
    }
    .btn-print:hover { background: #1560b8; transform: translateY(-1px); }

    .btn-back {
        display: inline-flex; align-items: center; gap: .45rem;
        padding: .75rem 1.5rem;
        background: rgba(255,255,255,.08); color: rgba(255,255,255,.75);
        border: 1px solid rgba(255,255,255,.15); border-radius: 10px;
        font-size: .92rem; font-weight: 700;
        cursor: pointer; text-decoration: none;
        transition: .15s;
    }
    .btn-back:hover { background: rgba(255,255,255,.14); color: #fff; }

    .print-hint {
        color: rgba(255,255,255,.35);
        font-size: .75rem;
        text-align: center;
    }
}

/* ═══════════════════════════════════════════
   TICKET — contenido real (pantalla + impresión)
═══════════════════════════════════════════ */
.ticket {
    font-family: 'Courier New', 'Lucida Console', monospace;
    font-size: 7.5pt;
    line-height: 1.35;
    color: #000;
    background: #fff;
    width: 100%;
    padding: 5px 6px;
    user-select: none;
}

/* Encabezado */
.t-brand {
    text-align: center;
    font-weight: bold;
    font-size: 9pt;
    letter-spacing: .08em;
    text-transform: uppercase;
    padding-bottom: 3px;
    border-bottom: 1px dashed #999;
    margin-bottom: 4px;
}
.t-brand-sub {
    text-align: center;
    font-size: 6.5pt;
    color: #555;
    margin-bottom: 3px;
}

/* Número de ticket destacado */
.t-folio {
    text-align: center;
    font-size: 8pt;
    font-weight: bold;
    letter-spacing: .06em;
    background: #000;
    color: #fff;
    padding: 2px 4px;
    margin: 2px 0 4px;
}

/* Sección */
.t-section {
    border-bottom: 1px dashed #bbb;
    padding-bottom: 3px;
    margin-bottom: 3px;
}
.t-section:last-child { border-bottom: none; }

/* Fila de dato */
.t-row {
    display: flex;
    justify-content: space-between;
    gap: 4px;
    padding: 1px 0;
}
.t-lbl {
    color: #555;
    flex-shrink: 0;
    max-width: 52%;
}
.t-val {
    font-weight: bold;
    text-align: right;
    word-break: break-all;
}
.t-val.accent { color: #000; }

/* Monto pagado grande */
.t-amount-box {
    text-align: center;
    border: 1px solid #000;
    margin: 4px 0 3px;
    padding: 2px 0;
}
.t-amount-big {
    font-size: 12pt;
    font-weight: bold;
    letter-spacing: .04em;
}
.t-amount-lbl {
    font-size: 6pt;
    color: #555;
    text-transform: uppercase;
    letter-spacing: .08em;
}

/* Pie */
.t-footer {
    text-align: center;
    font-size: 6pt;
    color: #777;
    padding-top: 3px;
    border-top: 1px dashed #bbb;
    margin-top: 3px;
}

/* Separador línea */
.t-line { border: none; border-top: 1px solid #ddd; margin: 3px 0; }

/* ═══════════════════════════════════════════
   IMPRESIÓN — 57mm x auto
═══════════════════════════════════════════ */
@media print {
    @page {
        size: 57mm auto;
        margin: 0;
    }

    html, body {
        width: 57mm;
        background: #fff !important;
        margin: 0;
        padding: 0;
    }

    /* Ocultar todo excepto el ticket */
    .page-title, .actions, .print-hint, .ticket-wrapper::before {
        display: none !important;
    }

    .ticket-wrapper {
        box-shadow: none !important;
        border-radius: 0 !important;
        width: 57mm !important;
        margin: 0 !important;
    }

    .ticket {
        font-size: 7.5pt;
        padding: 3px 4px;
    }
}
</style>
</head>
<body>

@php
    $t              = $ticket;
    $amountPaid     = number_format((float)($t['amount_paid']    ?? 0), 2);
    $totalAmount    = number_format((float)($t['total_amount']   ?? 0), 2);
    $remaining      = number_format((float)($t['remaining']      ?? 0), 2);
    $payNum         = $t['payment_number'] ?? '—';
    $payTotal       = $t['payments_total'] ?? '—';
    $methodMap = ['CASH'=>'Efectivo','TRANSFER'=>'Transferencia','CARD'=>'Tarjeta','OTHER'=>'Otro'];
    $method    = $methodMap[$t['payment_method'] ?? ''] ?? ($t['payment_method'] ?? '—');
    $loanId    = $t['loan_id'] ?? 0;
    $paidAtIso = $t['paid_at_iso'] ?? null;   // timestamp ISO para conversión local en JS
    $paidAtFmt = $t['paid_at'] ?? '';          // fallback formateado por el servidor
@endphp

{{-- PANTALLA: título y botones --}}
<div class="page-title">Vista previa del ticket — 57 mm</div>

<div class="ticket-wrapper" id="ticketEl">
    <div class="ticket">

        {{-- ENCABEZADO --}}
        <div class="t-brand">★ MYBANK ★</div>
        <div class="t-brand-sub">Financiera · Comprobante de pago</div>

        {{-- FOLIO --}}
        <div class="t-folio"># {{ $t['ticket_number'] ?? '—' }}</div>

        {{-- CLIENTE --}}
        <div class="t-section">
            <div class="t-row">
                <span class="t-lbl">No. Cliente</span>
                <span class="t-val">{{ $t['client_number'] ?? '—' }}</span>
            </div>
            <div class="t-row">
                <span class="t-lbl">Nombre</span>
                <span class="t-val">{{ mb_strimwidth($t['client_name'] ?? '—', 0, 22, '…') }}</span>
            </div>
        </div>

        {{-- MONTO PAGADO --}}
        <div class="t-amount-box">
            <div class="t-amount-lbl">Importe recibido</div>
            <div class="t-amount-big">${{ $amountPaid }}</div>
            <div class="t-amount-lbl">{{ $method }}</div>
        </div>

        {{-- DETALLE PRÉSTAMO --}}
        <div class="t-section">
            <div class="t-row">
                <span class="t-lbl">Pago No.</span>
                <span class="t-val accent">{{ $payNum }} / {{ $payTotal }}</span>
            </div>
            <div class="t-row">
                <span class="t-lbl">Total crédito</span>
                <span class="t-val">${{ $totalAmount }}</span>
            </div>
            <div class="t-row">
                <span class="t-lbl">Adeudo restante</span>
                <span class="t-val accent">${{ $remaining }}</span>
            </div>
        </div>

        {{-- NOTAS --}}
        @if(!empty($t['notes']))
        <div class="t-section">
            <div class="t-row">
                <span class="t-lbl">Nota</span>
                <span class="t-val" style="font-weight:normal;">{{ mb_strimwidth($t['notes'], 0, 28, '…') }}</span>
            </div>
        </div>
        @endif

        {{-- PIE — hora en tiempo local del dispositivo --}}
        <div class="t-footer">
            <span id="ticket-datetime">{{ $paidAtFmt }}</span><br>
            Conserve este comprobante<br>
            ¡Gracias por su pago!
        </div>

    </div>
</div>

{{-- BOTONES PANTALLA --}}
<div class="actions">
    <button class="btn-print" onclick="window.print()">
        <svg width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
            <path stroke-linecap="round" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
            <rect x="6" y="14" width="12" height="8" rx="1"/>
        </svg>
        Imprimir ticket
    </button>
    <a class="btn-back" href="{{ route('loans.show', ['loanId' => $loanId]) }}">
        ← Volver al préstamo
    </a>
</div>

<div class="print-hint">
    Asegúrate de seleccionar papel <strong style="color:rgba(255,255,255,.6);">57 mm</strong><br>
    y desactivar encabezado/pie de página en el diálogo de impresión.
</div>

<script>
// Hora local del dispositivo en el ticket
(function() {
    @if($paidAtIso)
        var iso = '{{ $paidAtIso }}';
        try {
            var d = new Date(iso);
            if (!isNaN(d.getTime())) {
                var dtEl = document.getElementById('ticket-datetime');
                if (dtEl) {
                    dtEl.textContent = d.toLocaleDateString('es-MX', {
                        day:   '2-digit',
                        month: '2-digit',
                        year:  'numeric'
                    }) + ' ' + d.toLocaleTimeString('es-MX', {
                        hour:   '2-digit',
                        minute: '2-digit',
                        hour12: false
                    });
                }
            }
        } catch(e) {}
    @endif
})();

// Auto-print al abrir si viene de un pago recién registrado
@if(session('ticket_data') || request()->query('autoprint'))
    window.addEventListener('load', function(){ setTimeout(function(){ window.print(); }, 500); });
@endif
</script>

</body>
</html>
