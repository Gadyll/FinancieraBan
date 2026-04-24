@extends('layouts.app')
@section('title', 'Nuevo Préstamo — MYBANK')

@push('styles')
<style>
/* Calculadora */
.calc-panel {
    position: sticky; top: 78px;
    background: var(--blue-dark);
    border-radius: 14px; overflow: hidden;
    box-shadow: 0 8px 32px rgba(18,89,176,.30);
    color: #fff;
}
.calc-head {
    background: rgba(255,255,255,.10); padding: 1rem 1.25rem;
    border-bottom: 1px solid rgba(255,255,255,.10);
    display: flex; align-items: center; gap: .6rem;
    font-weight: 800; font-size: .95rem;
}
.calc-body { padding: 1.25rem; }
.calc-row {
    display: flex; justify-content: space-between; align-items: baseline;
    padding: .5rem 0; border-bottom: 1px solid rgba(255,255,255,.08);
    gap: 1rem;
}
.calc-row:last-child { border-bottom: 0; }
.calc-lbl { color: rgba(255,255,255,.70); font-size: .88rem; font-weight: 500; }
.calc-val { font-weight: 900; font-size: 1rem; font-variant-numeric: tabular-nums; }
.calc-row.sep {
    border-top: 1.5px solid rgba(255,255,255,.20);
    padding-top: .75rem; margin-top: .25rem;
}
.calc-row.sep .calc-lbl { color: #fff; font-weight: 800; font-size: .92rem; }
.calc-row.sep .calc-val { font-size: 1.25rem; color: #7fffd4; }
.calc-row.cuota .calc-val { color: #ffd580; font-size: 1.1rem; }
.calc-row.dur .calc-val   { color: rgba(255,255,255,.90); }

/* Frecuencia botones */
.freq-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: .6rem; }
.freq-btn {
    display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .3rem;
    padding: .75rem .5rem; border-radius: 10px; cursor: pointer;
    border: 1.5px solid rgba(26,111,207,.20); background: #fafcff;
    font-family: 'Outfit', sans-serif; font-weight: 700; font-size: .85rem; color: var(--text-2);
    transition: .15s;
}
.freq-btn:hover { border-color: var(--blue); color: var(--blue); background: rgba(26,111,207,.05); }
.freq-btn.sel   { background: var(--blue); border-color: var(--blue); color: #fff; box-shadow: 0 4px 14px rgba(26,111,207,.3); }
.freq-btn svg   { opacity: .6; }
.freq-btn.sel svg { opacity: 1; }

/* Preview calendario */
.sch-list { max-height: 240px; overflow-y: auto; border-radius: 10px; border: 1px solid rgba(255,255,255,.10); margin-top: 1rem; }
.sch-item {
    display: grid; grid-template-columns: 28px 1fr auto;
    align-items: center; gap: .6rem;
    padding: .55rem .75rem; border-bottom: 1px solid rgba(255,255,255,.07);
    font-size: .85rem;
}
.sch-item:last-child { border-bottom: 0; }
.sch-n {
    width: 22px; height: 22px; border-radius: 50%;
    background: rgba(255,255,255,.12); color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: .72rem; font-weight: 800;
}
.sch-n.last { background: #7fffd4; color: var(--blue-dark); }
.sch-date { color: rgba(255,255,255,.75); }
.sch-amt  { font-weight: 800; color: #ffd580; }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <span class="breadcrumb-sep">›</span>
            <a href="{{ route('loans.index') }}">Préstamos</a>
            <span class="breadcrumb-sep">›</span>
            Nuevo
        </div>
        <h1 class="page-title">Nuevo Préstamo</h1>
        <p class="page-sub">Crea un crédito con cálculo automático de interés, IVA y calendario.</p>
    </div>
    <a href="{{ route('loans.index') }}" class="btn btn-ghost">← Volver</a>
</div>

@if($errors->any())
    <div class="alert alert-danger">⚠ {{ $errors->first('loan_create') ?: $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('loans.store') }}" id="loanForm">
@csrf

<div class="row g-3">

    {{-- ── COLUMNA: Formulario ── --}}
    <div class="col-12 col-lg-7">
        <div class="card">
            <div class="card-head card-accent-blue">
                <h2 class="card-title">Datos del préstamo</h2>
            </div>
            <div class="card-body">

                {{-- Cliente --}}
                <div class="field">
                    <label class="field-label field-required">Cliente</label>
                    <select class="field-input field-select" id="client_id" name="client_id" required>
                        <option value="">— Selecciona el cliente —</option>
                        @foreach($clients as $c)
                            <option value="{{ $c['id'] }}"
                                {{ (old('client_id', $selectedClientId) == $c['id']) ? 'selected' : '' }}>
                                {{ $c['full_name'] }} — {{ $c['client_number'] ?? '#'.$c['id'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="section-title" style="margin-top:1.25rem;">
                    <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Montos
                </div>

                <div class="grid-2">
                    <div class="field">
                        <label class="field-label field-required">Monto del préstamo ($)</label>
                        <input class="field-input" id="principal" name="principal_amount"
                               type="number" min="1" step="0.01"
                               value="{{ old('principal_amount', 1000) }}" required>
                    </div>
                    <div class="field">
                        <label class="field-label field-required">Tasa de interés (%)</label>
                        <input class="field-input" id="intRate" name="interest_rate"
                               type="number" min="0" max="1000" step="0.01"
                               value="{{ old('interest_rate', 20) }}" required>
                    </div>
                </div>

                <div class="field">
                    <label class="field-label field-required">Tasa IVA (%) — aplica sobre el interés</label>
                    <input class="field-input" id="ivaRate" name="iva_rate"
                           type="number" min="0" max="100" step="0.01"
                           value="{{ old('iva_rate', 16) }}" required>
                    <div class="field-hint">México: IVA estándar 16%. El capital NO genera IVA, solo el interés.</div>
                </div>

                <div class="section-title">
                    <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    Plazo
                </div>

                {{-- Frecuencia --}}
                <div class="field">
                    <label class="field-label field-required">Frecuencia de pago</label>
                    <div class="freq-grid">
                        <label class="freq-btn {{ old('frequency','WEEKLY')==='WEEKLY' ? 'sel' : '' }}" id="fb-WEEKLY">
                            <input type="radio" name="frequency" value="WEEKLY" style="display:none;" {{ old('frequency','WEEKLY')==='WEEKLY' ? 'checked' : '' }}>
                            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            Semanal
                        </label>
                        <label class="freq-btn {{ old('frequency')==='BIWEEKLY' ? 'sel' : '' }}" id="fb-BIWEEKLY">
                            <input type="radio" name="frequency" value="BIWEEKLY" style="display:none;" {{ old('frequency')==='BIWEEKLY' ? 'checked' : '' }}>
                            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>
                            </svg>
                            Quincenal
                        </label>
                        <label class="freq-btn {{ old('frequency')==='MONTHLY' ? 'sel' : '' }}" id="fb-MONTHLY">
                            <input type="radio" name="frequency" value="MONTHLY" style="display:none;" {{ old('frequency')==='MONTHLY' ? 'checked' : '' }}>
                            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                            Mensual
                        </label>
                    </div>
                    <div class="field-hint" id="freqHint">Semanal: 16–72 pagos (múltiplos de 4).</div>
                </div>

                <div class="grid-2">
                    <div class="field">
                        <label class="field-label field-required">Número de pagos</label>
                        <input class="field-input" id="numPays" name="payments_count"
                               type="number" min="1" max="520"
                               value="{{ old('payments_count', 16) }}" required>
                    </div>
                    <div class="field">
                        <label class="field-label field-required">Fecha de inicio</label>
                        <input class="field-input" id="startDate" name="start_date"
                               type="date" value="{{ old('start_date', date('Y-m-d')) }}"
                               min="{{ date('Y-m-d') }}" required>
                    </div>
                </div>

                <div style="display:flex;gap:.65rem;flex-wrap:wrap;margin-top:.5rem;">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <svg width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Crear préstamo
                    </button>
                    <a href="{{ route('loans.index') }}" class="btn btn-ghost btn-lg">Cancelar</a>
                </div>

            </div>
        </div>
    </div>

    {{-- ── COLUMNA: Calculadora ── --}}
    <div class="col-12 col-lg-5">
        <div class="calc-panel">
            <div class="calc-head">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/>
                    <line x1="8" y1="10" x2="16" y2="10"/><line x1="8" y1="14" x2="12" y2="14"/>
                </svg>
                Simulador en tiempo real
            </div>
            <div class="calc-body">
                <div class="calc-row">
                    <span class="calc-lbl">💰 Capital prestado</span>
                    <span class="calc-val" id="rc-principal">$0.00</span>
                </div>
                <div class="calc-row">
                    <span class="calc-lbl">📈 Interés (<span id="rc-rate">0</span>%)</span>
                    <span class="calc-val" id="rc-interest">$0.00</span>
                </div>
                <div class="calc-row">
                    <span class="calc-lbl">🧾 IVA (<span id="rc-iva-rate">16</span>%)</span>
                    <span class="calc-val" id="rc-iva">$0.00</span>
                </div>
                <div class="calc-row sep">
                    <span class="calc-lbl">TOTAL A PAGAR</span>
                    <span class="calc-val" id="rc-total">$0.00</span>
                </div>
                <div class="calc-row cuota">
                    <span class="calc-lbl">💳 Cuota por pago (<span id="rc-n">0</span>)</span>
                    <span class="calc-val" id="rc-cuota">$0.00</span>
                </div>
                <div class="calc-row dur">
                    <span class="calc-lbl">📅 Duración aproximada</span>
                    <span class="calc-val" id="rc-dur">—</span>
                </div>

                {{-- Preview calendario --}}
                <div style="margin-top:1.1rem;">
                    <div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;opacity:.7;margin-bottom:.4rem;">
                        Preview de cuotas
                    </div>
                    <div class="sch-list" id="schList">
                        <div class="sch-item" style="color:rgba(255,255,255,.5);font-style:italic;">
                            <span></span>
                            <span>Ingresa los datos...</span>
                            <span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
</form>
@endsection

@push('scripts')
<script>
(function(){
    var principal = document.getElementById('principal');
    var intRate   = document.getElementById('intRate');
    var ivaRate   = document.getElementById('ivaRate');
    var numPays   = document.getElementById('numPays');
    var startDate = document.getElementById('startDate');
    var freqBtns  = document.querySelectorAll('.freq-btn');
    var freqInputs= document.querySelectorAll('input[name="frequency"]');
    var freqHint  = document.getElementById('freqHint');

    var rcPrincipal = document.getElementById('rc-principal');
    var rcRate      = document.getElementById('rc-rate');
    var rcIvaRate   = document.getElementById('rc-iva-rate');
    var rcInterest  = document.getElementById('rc-interest');
    var rcIva       = document.getElementById('rc-iva');
    var rcTotal     = document.getElementById('rc-total');
    var rcCuota     = document.getElementById('rc-cuota');
    var rcN         = document.getElementById('rc-n');
    var rcDur       = document.getElementById('rc-dur');
    var schList     = document.getElementById('schList');

    function fmt(v){ return '$' + v.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','); }
    function fmtDate(d){ return d.toISOString().slice(0,10); }

    function getFreq(){
        for(var i=0;i<freqInputs.length;i++) if(freqInputs[i].checked) return freqInputs[i].value;
        return 'WEEKLY';
    }

    function nextDate(startStr, freq, n){
        var d = new Date(startStr + 'T00:00:00');
        if(freq === 'WEEKLY')   { d.setDate(d.getDate() + 7*(n-1)); return d; }
        if(freq === 'BIWEEKLY') { d.setDate(d.getDate() + 14*(n-1)); return d; }
        d.setMonth(d.getMonth() + (n-1)); return d;
    }

    function durText(freq, n){
        if(freq==='WEEKLY')   return Math.round(n/4) + ' mes(es)';
        if(freq==='BIWEEKLY') return Math.round(n/2) + ' mes(es)';
        return n + ' mes(es)';
    }

    var hints = {
        WEEKLY:   'Semanal: 16–72 pagos (múltiplos de 4).',
        BIWEEKLY: 'Quincenal: 10–36 pagos (múltiplos de 2).',
        MONTHLY:  'Mensual: 1–18 pagos.',
    };

    function calc(){
        var p  = parseFloat(principal.value) || 0;
        var r  = parseFloat(intRate.value)   || 0;
        var iv = parseFloat(ivaRate.value)   || 0;
        var n  = parseInt(numPays.value)     || 0;
        var sd = startDate.value;
        var freq = getFreq();

        var interest = p * (r / 100);
        var iva      = interest * (iv / 100);
        var total    = p + interest + iva;
        var cuota    = n > 0 ? total / n : 0;

        rcPrincipal.textContent = fmt(p);
        rcRate.textContent      = r;
        rcIvaRate.textContent   = iv;
        rcInterest.textContent  = fmt(interest);
        rcIva.textContent       = fmt(iva);
        rcTotal.textContent     = fmt(total);
        rcCuota.textContent     = fmt(cuota);
        rcN.textContent         = n;
        rcDur.textContent       = n > 0 ? durText(freq, n) : '—';

        // Preview cuotas
        if(n > 0 && p > 0 && sd){
            var shown = Math.min(n, 18);
            var acc = 0; var html = '';
            for(var i=1;i<=shown;i++){
                var amt = i === n ? (total - acc) : cuota;
                acc += (i < n ? cuota : 0);
                var last = (i === n);
                var due = nextDate(sd, freq, i);
                html += '<div class="sch-item">'
                    + '<div class="sch-n' + (last ? ' last' : '') + '">' + i + '</div>'
                    + '<span class="sch-date">' + fmtDate(due) + '</span>'
                    + '<span class="sch-amt">' + fmt(Math.max(0,amt)) + '</span>'
                    + '</div>';
            }
            if(n > 18){
                html += '<div class="sch-item" style="color:rgba(255,255,255,.45);font-style:italic;">'
                    + '<span></span><span>… y '+(n-18)+' cuotas más</span><span></span></div>';
            }
            schList.innerHTML = html;
        } else {
            schList.innerHTML = '<div class="sch-item" style="color:rgba(255,255,255,.45);font-style:italic;"><span></span><span>Ingresa los datos...</span><span></span></div>';
        }
    }

    // Frecuencia
    freqBtns.forEach(function(btn){
        btn.addEventListener('click', function(){
            freqBtns.forEach(function(b){ b.classList.remove('sel'); });
            btn.classList.add('sel');
            var inp = btn.querySelector('input[type="radio"]');
            if(inp){ inp.checked = true; freqHint.textContent = hints[inp.value] || ''; }
            calc();
        });
    });

    [principal, intRate, ivaRate, numPays, startDate].forEach(function(el){
        if(el) el.addEventListener('input', calc);
    });

    calc();
})();
</script>
@endpush
