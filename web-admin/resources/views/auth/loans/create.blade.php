@extends('layouts.app')
@section('title', 'Nuevo Préstamo — MYBANK')

@push('styles')
<style>
.calc-panel {
    position: sticky; top: 78px;
    background: var(--blue-dark); border-radius: 14px; overflow: hidden;
    box-shadow: 0 8px 32px rgba(18,89,176,.30); color: #fff;
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
    padding: .55rem 0; border-bottom: 1px solid rgba(255,255,255,.08); gap: 1rem;
}
.calc-row:last-child { border-bottom: 0; }
.calc-lbl { color: rgba(255,255,255,.70); font-size: .88rem; font-weight: 500; }
.calc-val { font-weight: 900; font-size: 1rem; font-variant-numeric: tabular-nums; }
.calc-row.sep   { border-top: 1.5px solid rgba(255,255,255,.20); padding-top: .75rem; margin-top: .25rem; }
.calc-row.sep .calc-lbl { color: #fff; font-weight: 800; font-size: .92rem; }
.calc-row.sep .calc-val { font-size: 1.25rem; color: #7fffd4; }
.calc-row.cuota .calc-val { color: #ffd580; font-size: 1.1rem; }
.calc-row.dur   .calc-val { color: rgba(255,255,255,.90); }
.calc-alert {
    background: rgba(255,213,128,.12); border: 1px solid rgba(255,213,128,.30);
    border-radius: 8px; padding: .6rem .9rem; margin-top: .6rem;
    font-size: .80rem; color: #ffd580; display: none;
}
.freq-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: .6rem; }
.freq-btn {
    display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .3rem;
    padding: .75rem .5rem; border-radius: 10px; cursor: pointer;
    border: 1.5px solid rgba(26,111,207,.20); background: #fafcff;
    font-family: 'Outfit', sans-serif; font-weight: 700; font-size: .85rem; color: var(--text-2);
    transition: .15s;
}
.freq-btn:hover { border-color: var(--blue); color: var(--blue); background: rgba(26,111,207,.05); }
.freq-btn.sel   { background: var(--blue); border-color: var(--blue); color: #fff; box-shadow: 0 4px 14px rgba(26,111,207,.3); }
.sch-list { max-height: 260px; overflow-y: auto; border-radius: 10px; border: 1px solid rgba(255,255,255,.10); margin-top: 1rem; }
.sch-item {
    display: grid; grid-template-columns: 28px 1fr auto;
    align-items: center; gap: .5rem;
    padding: .5rem .75rem; border-bottom: 1px solid rgba(255,255,255,.07); font-size: .84rem;
}
.sch-item:last-child { border-bottom: 0; }
.sch-n {
    width: 22px; height: 22px; border-radius: 50%;
    background: rgba(255,255,255,.12); color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: .70rem; font-weight: 800;
}
.sch-n.last { background: #7fffd4; color: var(--blue-dark); }
.sch-date { color: rgba(255,255,255,.75); }
.sch-amt  { font-weight: 800; color: #ffd580; }
.override-box {
    background: rgba(255,213,128,.08); border: 1px solid rgba(255,213,128,.25);
    border-radius: 10px; padding: .9rem 1rem; margin-top: .75rem;
}
/* Rate select styling */
.rate-select {
    appearance: none; -webkit-appearance: none;
    background: #fafcff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' viewBox='0 0 24 24' stroke='%236b7280' stroke-width='3'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E") right 12px center no-repeat;
    padding-right: 2.5rem;
}
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
        <p class="page-sub">Capital + Tabla de Tasas Oficial · Interés congelado a 16 semanas</p>
    </div>
    <a href="{{ route('loans.index') }}" class="btn btn-ghost">← Volver</a>
</div>

@if($errors->any())
    <div class="alert alert-danger">⚠ {{ $errors->first('loan_create') ?: $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('loans.store') }}" id="loanForm">
@csrf

<div class="row g-3">

    {{-- ── Formulario ── --}}
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

                <div class="section-title" style="margin-top:1.5rem;">
                    <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Montos
                </div>

                <div class="grid-2">
                    <div class="field">
                        <label class="field-label field-required">Capital prestado ($)</label>
                        <input class="field-input" id="principal" name="principal_amount"
                               type="number" min="1" step="1"
                               value="{{ old('principal_amount') }}" required>
                        <div class="field-hint">Dinero que se entrega al cliente</div>
                    </div>
                    <div class="field">
                        <label class="field-label field-required">Tasa de interés (%)</label>
                        <select class="field-input field-select rate-select" id="interestRate" name="interest_rate" required>
                            <option value="">— Seleccionar tasa —</option>
                            <option value="3.50" {{ old('interest_rate')=='3.50'?'selected':'' }}>3.50% — $71.25/mil</option>
                            <option value="3.60" {{ old('interest_rate')=='3.60'?'selected':'' }}>3.60% — $71.50/mil</option>
                            <option value="3.70" {{ old('interest_rate')=='3.70'?'selected':'' }}>3.70% — $71.75/mil</option>
                            <option value="3.80" {{ old('interest_rate')=='3.80'?'selected':'' }}>3.80% — $72.00/mil</option>
                            <option value="3.90" {{ old('interest_rate')=='3.90'?'selected':'' }}>3.90% — $72.25/mil</option>
                            <option value="4.00" {{ old('interest_rate')=='4.00'?'selected':'' }}>4.00% — $72.50/mil</option>
                            <option value="4.10" {{ old('interest_rate')=='4.10'?'selected':'' }}>4.10% — $72.75/mil</option>
                            <option value="4.20" {{ old('interest_rate')=='4.20'?'selected':'' }}>4.20% — $73.00/mil</option>
                            <option value="4.30" {{ old('interest_rate')=='4.30'?'selected':'' }}>4.30% — $73.25/mil</option>
                            <option value="4.40" {{ old('interest_rate')=='4.40'?'selected':'' }}>4.40% — $73.50/mil</option>
                            <option value="4.50" {{ old('interest_rate')=='4.50'?'selected':'' }}>4.50% — $73.75/mil</option>
                            <option value="4.60" {{ old('interest_rate')=='4.60'?'selected':'' }}>4.60% — $74.00/mil</option>
                            <option value="4.70" {{ old('interest_rate')=='4.70'?'selected':'' }}>4.70% — $74.25/mil</option>
                            <option value="4.80" {{ old('interest_rate')=='4.80'?'selected':'' }}>4.80% — $74.50/mil</option>
                            <option value="4.90" {{ old('interest_rate')=='4.90'?'selected':'' }}>4.90% — $74.75/mil</option>
                            <option value="5.00" {{ old('interest_rate')=='5.00'?'selected':'' }}>5.00% — $75.00/mil</option>
                            <option value="5.10" {{ old('interest_rate')=='5.10'?'selected':'' }}>5.10% — $75.25/mil</option>
                            <option value="5.20" {{ old('interest_rate')=='5.20'?'selected':'' }}>5.20% — $75.50/mil</option>
                            <option value="5.30" {{ old('interest_rate')=='5.30'?'selected':'' }}>5.30% — $75.75/mil</option>
                            <option value="5.40" {{ old('interest_rate')=='5.40'?'selected':'' }}>5.40% — $76.00/mil</option>
                            <option value="5.50" {{ old('interest_rate')=='5.50'?'selected':'' }}>5.50% — $76.25/mil</option>
                            <option value="5.60" {{ old('interest_rate')=='5.60'?'selected':'' }}>5.60% — $76.50/mil</option>
                            <option value="5.70" {{ old('interest_rate')=='5.70'?'selected':'' }}>5.70% — $76.75/mil</option>
                            <option value="5.80" {{ old('interest_rate')=='5.80'?'selected':'' }}>5.80% — $77.00/mil</option>
                            <option value="5.90" {{ old('interest_rate')=='5.90'?'selected':'' }}>5.90% — $77.25/mil</option>
                            <option value="6.00" {{ old('interest_rate')=='6.00'?'selected':'' }}>6.00% — $77.50/mil</option>
                            <option value="6.10" {{ old('interest_rate')=='6.10'?'selected':'' }}>6.10% — $77.75/mil</option>
                            <option value="6.20" {{ old('interest_rate')=='6.20'?'selected':'' }}>6.20% — $78.00/mil</option>
                            <option value="6.30" {{ old('interest_rate')=='6.30'?'selected':'' }}>6.30% — $78.25/mil</option>
                            <option value="6.40" {{ old('interest_rate')=='6.40'?'selected':'' }}>6.40% — $78.50/mil</option>
                            <option value="6.50" {{ old('interest_rate')=='6.50'?'selected':'' }}>6.50% — $78.75/mil</option>
                            <option value="6.60" {{ old('interest_rate')=='6.60'?'selected':'' }}>6.60% — $79.00/mil</option>
                            <option value="6.70" {{ old('interest_rate')=='6.70'?'selected':'' }}>6.70% — $79.25/mil</option>
                            <option value="6.80" {{ old('interest_rate')=='6.80'?'selected':'' }}>6.80% — $79.50/mil</option>
                            <option value="6.90" {{ old('interest_rate')=='6.90'?'selected':'' }}>6.90% — $79.75/mil</option>
                            <option value="7.00" {{ old('interest_rate')=='7.00'?'selected':'' }}>7.00% — $80.00/mil</option>
                            <option value="7.10" {{ old('interest_rate')=='7.10'?'selected':'' }}>7.10% — $80.25/mil</option>
                            <option value="7.20" {{ old('interest_rate')=='7.20'?'selected':'' }}>7.20% — $80.50/mil</option>
                            <option value="7.30" {{ old('interest_rate')=='7.30'?'selected':'' }}>7.30% — $80.75/mil</option>
                            <option value="7.40" {{ old('interest_rate')=='7.40'?'selected':'' }}>7.40% — $81.00/mil</option>
                            <option value="7.50" {{ old('interest_rate')=='7.50'?'selected':'' }}>7.50% — $81.25/mil</option>
                        </select>
                        <div class="field-hint">Tabla oficial de tasas autorizadas</div>
                    </div>
                </div>

                <div class="section-title">
                    <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    Plazo y frecuencia
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
                        <label class="freq-btn {{ old('frequency')==='YEARLY' ? 'sel' : '' }}" id="fb-YEARLY">
                            <input type="radio" name="frequency" value="YEARLY" style="display:none;" {{ old('frequency')==='YEARLY' ? 'checked' : '' }}>
                            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Anual
                        </label>
                    </div>
                    <div class="field-hint" id="freqHint">Selecciona una frecuencia.</div>
                </div>

                <div class="grid-2">
                    <div class="field">
                        <label class="field-label field-required">Número de pagos</label>
                        <input class="field-input" id="numPays" name="payments_count"
                               type="number" min="1" max="104"
                               value="{{ old('payments_count') }}" required>
                        <div class="field-hint" id="numPayHint">Mín. 4 · Máx. 104</div>
                    </div>
                    <div class="field">
                        <label class="field-label field-required">Fecha de entrega del dinero</label>
                        <input class="field-input" id="startDate" name="start_date"
                               type="date" value="{{ old('start_date') }}" required>
                        <div class="field-hint">A partir de esta fecha corren los pagos</div>
                    </div>
                </div>

                {{-- Override de cuota --}}
                <div class="override-box">
                    <label style="font-size:.85rem;font-weight:700;color:var(--text-1);display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem;">
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Cuota de cobro manual (opcional)
                    </label>
                    <input class="field-input" id="payOverride" name="payment_amount_override"
                           type="number" min="0" step="1"
                           placeholder="Dejar vacío = usar cuota calculada automáticamente"
                           value="{{ old('payment_amount_override') }}">
                    <div class="field-hint" style="margin-top:.35rem;">
                        Establece una cuota fija manual para todos los pagos (todos los pagos serán esa cuota exacta).
                    </div>
                </div>

                <div style="display:flex;gap:.65rem;flex-wrap:wrap;margin-top:1.25rem;">
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

    {{-- ── Calculadora ── --}}
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
                    <span class="calc-lbl">💰 Capital</span>
                    <span class="calc-val" id="rc-principal">$0</span>
                </div>
                <div class="calc-row">
                    <span class="calc-lbl">📊 Tasa / Factor</span>
                    <span class="calc-val" id="rc-rate" style="font-size:.9rem;">—</span>
                </div>
                <div class="calc-row">
                    <span class="calc-lbl">🧮 Pago base (16 sem)</span>
                    <span class="calc-val" id="rc-base16" style="color:rgba(255,255,255,.7);">—</span>
                </div>
                <div class="calc-row">
                    <span class="calc-lbl">📈 Interés generado</span>
                    <span class="calc-val" id="rc-interest" style="color:rgba(255,255,255,.7);">—</span>
                </div>
                <div class="calc-row sep">
                    <span class="calc-lbl">TOTAL CONGELADO</span>
                    <span class="calc-val" id="rc-total">$0</span>
                </div>
                <div class="calc-row cuota">
                    <span class="calc-lbl">💳 Cuota (<span id="rc-n">0</span> pagos)</span>
                    <span class="calc-val" id="rc-cuota">$0</span>
                </div>
                <div class="calc-row dur">
                    <span class="calc-lbl">📅 Duración</span>
                    <span class="calc-val" id="rc-dur">—</span>
                </div>
                <div class="calc-row" id="rc-diff-row" style="display:none;">
                    <span class="calc-lbl">⚙️ Ajuste de fondo</span>
                    <span class="calc-val" id="rc-diff" style="color:#f97316;"></span>
                </div>
                <div class="calc-alert" id="rc-alert">
                    ⚠️ La cuota manual supera el total.
                </div>

                {{-- Preview calendario --}}
                <div style="margin-top:1rem;">
                    <div style="font-size:.77rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;opacity:.7;margin-bottom:.4rem;">
                        Preview de cuotas
                    </div>
                    <div class="sch-list" id="schList">
                        <div class="sch-item" style="color:rgba(255,255,255,.5);font-style:italic;">
                            <span></span>
                            <span>Selecciona capital y tasa...</span>
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
    // ── Tabla de Tasas Oficial (embebida) ──
    var TABLA = {
        "3.50":71.25, "3.60":71.50, "3.70":71.75, "3.80":72.00, "3.90":72.25,
        "4.00":72.50, "4.10":72.75, "4.20":73.00, "4.30":73.25, "4.40":73.50,
        "4.50":73.75, "4.60":74.00, "4.70":74.25, "4.80":74.50, "4.90":74.75,
        "5.00":75.00, "5.10":75.25, "5.20":75.50, "5.30":75.75, "5.40":76.00,
        "5.50":76.25, "5.60":76.50, "5.70":76.75, "5.80":77.00, "5.90":77.25,
        "6.00":77.50, "6.10":77.75, "6.20":78.00, "6.30":78.25, "6.40":78.50,
        "6.50":78.75, "6.60":79.00, "6.70":79.25, "6.80":79.50, "6.90":79.75,
        "7.00":80.00, "7.10":80.25, "7.20":80.50, "7.30":80.75, "7.40":81.00,
        "7.50":81.25
    };

    var principal    = document.getElementById('principal');
    var interestRate = document.getElementById('interestRate');
    var numPays      = document.getElementById('numPays');
    var startDate    = document.getElementById('startDate');
    var payOverride  = document.getElementById('payOverride');
    var freqBtns     = document.querySelectorAll('.freq-btn');
    var freqInputs   = document.querySelectorAll('input[name="frequency"]');
    var freqHint     = document.getElementById('freqHint');
    var numPayHint   = document.getElementById('numPayHint');

    var rcPrincipal  = document.getElementById('rc-principal');
    var rcRate       = document.getElementById('rc-rate');
    var rcBase16     = document.getElementById('rc-base16');
    var rcInterest   = document.getElementById('rc-interest');
    var rcTotal      = document.getElementById('rc-total');
    var rcCuota      = document.getElementById('rc-cuota');
    var rcN          = document.getElementById('rc-n');
    var rcDur        = document.getElementById('rc-dur');
    var rcDiffRow    = document.getElementById('rc-diff-row');
    var rcDiff       = document.getElementById('rc-diff');
    var rcAlert      = document.getElementById('rc-alert');
    var schList      = document.getElementById('schList');

    function fmt(v){ return '$' + Math.round(v).toLocaleString('es-MX'); }
    function fmt2(v){ return '$' + v.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','); }

    function getFreq(){
        for(var i=0;i<freqInputs.length;i++) if(freqInputs[i].checked) return freqInputs[i].value;
        return 'WEEKLY';
    }

    function nextDate(startStr, freq, n){
        var d = new Date(startStr + 'T12:00:00');
        if(freq === 'WEEKLY')   { d.setDate(d.getDate() + 7*(n-1)); return d; }
        if(freq === 'BIWEEKLY') { d.setDate(d.getDate() + 14*(n-1)); return d; }
        if(freq === 'YEARLY')   { d.setFullYear(d.getFullYear() + (n-1)); return d; }
        d.setMonth(d.getMonth() + (n-1)); return d;
    }

    function fmtDate(d){
        var days = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
        return days[d.getDay()] + ' ' + d.toISOString().slice(0,10);
    }

    function durText(freq, n){
        if(freq==='WEEKLY')   return (n/4).toFixed(1).replace('.0','') + ' mes(es) · ' + n + ' semanas';
        if(freq==='BIWEEKLY') return (n/2).toFixed(1).replace('.0','') + ' mes(es) · ' + n + ' quincenas';
        if(freq==='YEARLY')   return n + ' año(s)';
        return n + ' mes(es)';
    }

    var hints = {
        WEEKLY:   'Semanal · Mín. 16 semanas (4 meses) · Máx. 104',
        BIWEEKLY: 'Quincenal · Mín. 8 pagos (4 meses) · Máx. 52',
        MONTHLY:  'Mensual · Mín. 4 meses · Máx. 24',
        YEARLY:   'Anual · Mín. 1 año · Máx. 10',
    };
    var minPays = { WEEKLY:16, BIWEEKLY:8, MONTHLY:4, YEARLY:1 };
    var maxPays = { WEEKLY:104, BIWEEKLY:52, MONTHLY:24, YEARLY:10 };

    function calc(){
        var p    = parseFloat(principal.value)   || 0;
        var rate = interestRate.value;            // string key like "5.40"
        var n    = parseInt(numPays.value)       || 0;
        var sd   = startDate.value;
        var ov   = parseFloat(payOverride.value) || 0;
        var freq = getFreq();

        var factor = TABLA[rate] || 0;

        // ── Algoritmo Tabla de Tasas ──
        // 1. pago_base_16 = (capital / 1000) × factor
        // 2. total_congelado = pago_base_16 × 16
        // 3. interés = total - capital
        // 4. cuota = total / n_pagos
        var miles = p / 1000;
        var pagoBase16 = miles * factor;
        var total = pagoBase16 * 16;
        var interes = total - p;
        var cuota = n > 0 ? total / n : 0;
        var cuotaCobro = ov > 0 ? ov : cuota;
        var totalCobrado = cuotaCobro * n;

        // ── Actualizar panel ──
        rcPrincipal.textContent = p > 0 ? fmt(p) : '—';
        rcRate.textContent      = factor > 0 ? rate + '% → $' + factor.toFixed(2) + '/mil' : '—';
        rcBase16.textContent    = (p > 0 && factor > 0) ? fmt2(pagoBase16) + ' × 16 sem' : '—';
        rcInterest.textContent  = (p > 0 && factor > 0) ? fmt(interes) : '—';
        rcTotal.textContent     = (p > 0 && factor > 0) ? fmt(total) : '—';
        rcCuota.textContent     = (n > 0 && total > 0) ? fmt2(cuotaCobro) : '—';
        rcN.textContent         = n > 0 ? n : '—';
        rcDur.textContent       = (n > 0 && p > 0) ? durText(freq, n) : '—';

        // Ajuste de fondo (override)
        if(n > 0 && p > 0 && ov > 0 && Math.abs(totalCobrado - total) > 0.01){
            rcDiffRow.style.display = 'flex';
            var diff = totalCobrado - total;
            rcDiff.textContent = 'Total: ' + fmt(totalCobrado) + (diff > 0 ? ' (+' + fmt(diff) + ' fondo)' : ' (-' + fmt(Math.abs(diff)) + ')');
        } else {
            rcDiffRow.style.display = 'none';
        }
        rcAlert.style.display = 'none';

        // ── Preview de cuotas ──
        if(n > 0 && p > 0 && factor > 0 && sd){
            var html = '';
            for(var i=1;i<=n;i++){
                var due = nextDate(sd, freq, i);
                var amt;
                if(ov > 0){
                    amt = cuotaCobro;
                } else {
                    // última cuota ajusta diferencia
                    amt = (i === n) ? (total - cuota * (n - 1)) : cuota;
                }
                var isLast    = (i === n);
                var isWeekend = (due.getDay() === 0 || due.getDay() === 6);
                var rowStyle  = isWeekend ? 'background:rgba(245,138,0,.10);' : '';
                var warnTag   = isWeekend ? ' <span style="font-size:.65rem;color:#f97316;font-weight:700;">⚠ Finde</span>' : '';
                html += '<div class="sch-item" style="' + rowStyle + '">'
                    + '<div class="sch-n' + (isLast ? ' last' : '') + '">' + i + '</div>'
                    + '<span class="sch-date">' + fmtDate(due) + warnTag + '</span>'
                    + '<span class="sch-amt">' + fmt2(Math.max(0, amt)) + '</span>'
                    + '</div>';
            }
            schList.innerHTML = html;
        } else {
            schList.innerHTML = '<div class="sch-item" style="color:rgba(255,255,255,.45);font-style:italic;"><span></span><span>Selecciona capital y tasa...</span><span></span></div>';
        }
    }

    freqBtns.forEach(function(btn){
        btn.addEventListener('click', function(){
            freqBtns.forEach(function(b){ b.classList.remove('sel'); });
            btn.classList.add('sel');
            var inp = btn.querySelector('input[type="radio"]');
            if(inp){
                inp.checked = true;
                var f = inp.value;
                freqHint.textContent = hints[f] || '';
                numPayHint.textContent = 'Mín. ' + minPays[f] + ' · Máx. ' + maxPays[f];
                numPays.min = minPays[f];
                numPays.max = maxPays[f];
                if(parseInt(numPays.value) < minPays[f]) numPays.value = minPays[f];
            }
            calc();
        });
    });

    [principal, interestRate, numPays, startDate, payOverride].forEach(function(el){
        if(el) el.addEventListener('input', calc);
        if(el) el.addEventListener('change', calc);
    });

    // Trigger initial state sync for the selected frequency
    var initialSel = document.querySelector('.freq-btn.sel');
    if(initialSel) {
        var inp = initialSel.querySelector('input[type="radio"]');
        if(inp) {
            var f = inp.value;
            freqHint.textContent = hints[f] || '';
            numPayHint.textContent = 'Mín. ' + minPays[f] + ' · Máx. ' + maxPays[f];
            numPays.min = minPays[f];
            numPays.max = maxPays[f];
        }
    }

    calc();
})();
</script>
@endpush
