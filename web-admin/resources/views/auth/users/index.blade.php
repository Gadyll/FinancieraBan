@extends('layouts.app')

@section('title', 'Usuarios (Cobradores) - MYBANK')

@push('styles')
<style>
  .cardx {
    background: #fff;
    border: 1px solid rgba(26,111,207,.14);
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(13,27,46,.08);
    overflow: hidden;
  }
  .cardx-head {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid rgba(26,111,207,.10);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
  }
  .cardx-title { margin: 0; font-weight: 900; letter-spacing: -.02em; }
  .cardx-sub { margin: .25rem 0 0 0; color: #6b7e96; font-weight: 500; }
  .cardx-body { padding: 1.25rem; }

  .grid2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
  }
  @media (max-width: 900px) { .grid2 { grid-template-columns: 1fr; } }

  .field-label { display:block; font-weight:800; font-size:.88rem; color:#3a4d65; margin-bottom:.45rem;}
  .field-input {
    width: 100%;
    padding: .75rem 1rem;
    border: 1.5px solid rgba(26,111,207,.16);
    border-radius: 12px;
    background: #f8faff;
    outline: none;
    transition: .15s;
    font-family: 'Outfit', sans-serif;
  }
  .field-input:focus {
    background:#fff;
    border-color: rgba(26,111,207,.45);
    box-shadow: 0 0 0 3px rgba(26,111,207,.12);
  }

  .pass-wrap { position: relative; }
  .pass-eye {
    position:absolute;
    right:.65rem;
    top:50%;
    transform: translateY(-50%);
    border:0;
    background:transparent;
    padding:.35rem;
    border-radius:10px;
    cursor:pointer;
    color:#6b7e96;
  }
  .pass-eye:hover { color:#1a6fcf; }

  .pass-rules {
    margin-top:.65rem;
    border: 1px dashed rgba(26,111,207,.18);
    background: rgba(26,111,207,.04);
    border-radius: 12px;
    padding: .75rem .9rem;
    display:flex;
    flex-direction:column;
    gap:.35rem;
    font-size: .88rem;
    color:#3a4d65;
  }
  .rule { display:flex; align-items:center; gap:.5rem; }
  .dot {
    width:10px; height:10px; border-radius:999px;
    background: rgba(224,58,58,.70);
    box-shadow: 0 0 0 4px rgba(224,58,58,.10);
    flex-shrink:0;
  }
  .rule.ok .dot {
    background: rgba(18,169,138,.95);
    box-shadow: 0 0 0 4px rgba(18,169,138,.12);
  }

  .btnx {
    border: 0;
    border-radius: 12px;
    padding: .75rem 1rem;
    font-weight: 900;
    font-family: 'Outfit', sans-serif;
    cursor:pointer;
    transition:.15s;
    display:inline-flex;
    align-items:center;
    gap:.5rem;
    white-space:nowrap;
  }
  .btnx-primary {
    color:#fff;
    background: linear-gradient(135deg, #1a6fcf 0%, #1259b0 100%);
    box-shadow: 0 4px 18px rgba(26,111,207,.25);
  }
  .btnx-primary:hover { filter: brightness(1.06); transform: translateY(-1px); }
  .btnx-soft {
    background: rgba(26,111,207,.08);
    border: 1px solid rgba(26,111,207,.18);
    color:#1a6fcf;
  }
  .btnx-danger {
    background: rgba(224,58,58,.10);
    border: 1px solid rgba(224,58,58,.22);
    color:#b02020;
  }

  .table-wrap { border-radius: 16px; overflow:auto; border: 1px solid rgba(26,111,207,.14); }
  table { width:100%; border-collapse: collapse; min-width: 860px; }
  th, td { padding: .85rem .9rem; border-bottom: 1px solid rgba(26,111,207,.10); vertical-align: middle; }
  th {
    font-size: .78rem;
    letter-spacing: .10em;
    text-transform: uppercase;
    color:#6b7e96;
    background: rgba(26,111,207,.04);
    font-weight: 900;
  }
  tr:hover td { background: rgba(26,111,207,.03); }

  /* Badges PRO (solo corregimos legibilidad del texto) */
  /* Badges PRO (solo corregimos legibilidad del texto) */
  .badge{
    display:inline-flex;
    align-items:center;
    gap: 8px;
    padding: 7px 12px;
    border-radius: 999px;

    /* ✅ antes tenías un background rojo fijo (#7c0c0c) que ensucia todo */
    background: #f8fafc;                 /* base neutra (no transparente) */
    border: 1.5px solid rgba(15,23,42,.14);

    font-weight: 1000;
    font-size: 13px;
    letter-spacing: .02em;

    /* ✅ texto SIEMPRE legible */
    color: #0f172a;

    line-height: 1;
    white-space: nowrap;
  }

  /* ROLES */
  .b-admin{
    background: #0565e1;                 /* azul suave sólido */
    color:#1D4ED8;                       /* azul fuerte */
    border-color: #1a89a4;
  }

  .b-user{
    background: #110357;                 /* verde suave sólido */
    color:#047857;                       /* verde fuerte */
    border-color: #010380;
  }

  /* ESTADOS */
  .b-active{
    background: #005105;                 /* verde suave sólido */
    color:#065F46;                       /* verde oscuro */
    border-color: #159606;
  }

  .b-inactive{
    background: #de0b0b;                 /* rojo suave sólido */
    color:#B91C1C;                       /* rojo fuerte */
    border-color: #ab0808;
  }

  /* (Opcional) mejora contraste dentro de fila hover */
  tr:hover .badge{
    filter: brightness(0.98);
  }

  /* Modal */
  .modalx-backdrop {
    position: fixed; inset:0;
    background: rgba(119, 6, 6, 0.55);
    display:none;
    align-items:center;
    justify-content:center;
    padding: 1rem;
    z-index: 9999;
  }
  .modalx {
    width: min(560px, 100%);
    background:#fff;
    border-radius: 18px;
    box-shadow: 0 24px 64px rgba(0,0,0,.25);
    overflow:hidden;
    border:1px solid rgba(26,111,207,.14);
    animation: pop .18s ease-out both;
  }
  @keyframes pop { from { transform: translateY(10px) scale(.98); opacity:.6 } to { transform: translateY(0) scale(1); opacity:1 } }
  .modalx-head { padding: 1rem 1.25rem; border-bottom: 1px solid rgba(26,111,207,.10); }
  .modalx-title { margin:0; font-weight: 1000; }
  .modalx-body { padding: 1rem 1.25rem; color:#3a4d65; }
  .modalx-foot { padding: 1rem 1.25rem; border-top: 1px solid rgba(26,111,207,.10); display:flex; gap:.6rem; justify-content:flex-end; flex-wrap:wrap; }
  .kv { display:grid; grid-template-columns: 120px 1fr; gap:.35rem .75rem; margin-top:.75rem; font-size:.92rem; }
  .kv div:nth-child(odd) { color:#6b7e96; font-weight:800; }
  .muted { color:#6b7e96; }

  /* ===== FIX: QUITAR 2do OJO AUTOMATICO DEL NAVEGADOR (EDGE/CHROME) ===== */
  input[type="password"]::-ms-reveal,
  input[type="password"]::-ms-clear{
    display:none;
    width:0;
    height:0;
  }

  /* Chrome/Edge (autofill/cred button) */
  input[type="password"]::-webkit-credentials-auto-fill-button{
    visibility:hidden;
    display:none !important;
    pointer-events:none;
    position:absolute;
    right:0;
  }
  /* ===== ACCIONES: botones ordenados y mismo tamaño ===== */
.actions-wrap{
  display: grid;
  grid-template-columns: repeat(2, 160px);  /* 2 columnas, mismo ancho */
  gap: 10px;
  justify-content: start;
  align-items: center;
}

.actions-wrap .btnx{
  width: 160px;              /* mismo tamaño */
  justify-content: center;   /* texto centrado */
  text-align: center;
  padding: .75rem 0;         /* alto consistente */
}

/* En pantallas chicas: 1 columna */
@media (max-width: 900px){
  .actions-wrap{
    grid-template-columns: 1fr;
  }
  .actions-wrap .btnx{
    width: 100%;
  }
}
</style>
@endpush

@section('content')
<div class="container page">
  <div class="page-head">
    <div>
      <h1 class="page-title">Usuarios (Cobradores)</h1>
      <p class="page-sub">Crea y administra cobradores que usarán la app móvil (username/email/contraseña).</p>
    </div>
  </div>

  @if(session('ok'))
    <div class="alert alert-success surface surface-pad" style="border-radius:16px;">
      <strong>{{ session('ok') }}</strong>
    </div>
  @endif

  @if($error)
    <div class="alert alert-danger surface surface-pad" style="border-radius:16px;">
      <strong>{{ $error }}</strong>
    </div>
  @endif

  @if($errors->has('users'))
    <div class="alert alert-danger surface surface-pad" style="border-radius:16px;">
      <strong>{{ $errors->first('users') }}</strong>
    </div>
  @endif

  <div class="cardx" style="margin-bottom:1.25rem;">
    <div class="cardx-head">
      <div>
        <h3 class="cardx-title">Crear cobrador (USER)</h3>
        <p class="cardx-sub">Estos datos serán los mismos para iniciar sesión en la app móvil.</p>
      </div>
    </div>

    <div class="cardx-body">
      <form method="POST" action="{{ route('users.store') }}" id="createUserForm" autocomplete="off">
        @csrf

        <div class="grid2">
          <div>
            <label class="field-label" for="username">Username</label>
            <input class="field-input" id="username" name="username" type="text"
                   value="{{ old('username') }}" placeholder="cobrador01" required>
            @error('username')
              <div style="color:#b02020; font-weight:700; margin-top:.35rem;">{{ $message }}</div>
            @enderror
          </div>

          <div>
            <label class="field-label" for="email">Correo</label>
            <input class="field-input" id="email" name="email" type="email"
                   value="{{ old('email') }}" placeholder="cobrador@dominio.com" required>
            @error('email')
              <div style="color:#b02020; font-weight:700; margin-top:.35rem;">{{ $message }}</div>
            @enderror
          </div>
        </div>

        <div style="margin-top:1rem;">
          <label class="field-label" for="password">Contraseña</label>

          <div class="pass-wrap">
            <input class="field-input" id="password" name="password" type="password"
                   value="{{ old('password') }}" placeholder="Mínimo 8, mayúscula, número, especial" required
                   autocomplete="new-password" style="padding-right:3rem;">
            <button type="button" class="pass-eye" id="togglePass" aria-label="Mostrar contraseña">
              <svg id="eyeIcon" width="18" height="18" fill="none" viewBox="0 0 24 24"
                   stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M1 12S5 4 12 4s11 8 11 8-4 8-11 8S1 12 1 12z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>

          <div class="pass-rules" id="passRules">
            <div class="rule" data-rule="len"><span class="dot"></span> Mínimo 8 caracteres</div>
            <div class="rule" data-rule="upper"><span class="dot"></span> Al menos 1 mayúscula</div>
            <div class="rule" data-rule="num"><span class="dot"></span> Al menos 1 número</div>
            <div class="rule" data-rule="spec"><span class="dot"></span> Al menos 1 caracter especial (!@#$...)</div>
          </div>

          @error('password')
            <div style="color:#b02020; font-weight:700; margin-top:.35rem;">{{ $message }}</div>
          @enderror
        </div>

        <div style="margin-top:1rem; display:flex; gap:.75rem; flex-wrap:wrap;">
          <button class="btnx btnx-primary" type="submit" id="createSubmitBtn">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24"
                 stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
            </svg>
            Crear cobrador
          </button>

          <button class="btnx btnx-soft" type="button" id="clearFormBtn">
            Limpiar
          </button>

          <span class="muted" style="align-self:center;"> <strong></strong></span>
        </div>
      </form>
    </div>
  </div>

  <div class="cardx">
    <div class="cardx-head">
      <div>
        <h3 class="cardx-title">Lista de usuarios</h3>
        <p class="cardx-sub">ADMIN no se modifica. USER se puede desactivar o eliminar (si no tiene historial).</p>
      </div>
    </div>

    <div class="cardx-body">
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th style="width:90px;">ID</th>
              <th>Username</th>
              <th>Correo</th>
              <th style="width:130px;">Rol</th>
              <th style="width:140px;">Estado</th>
              <th style="width:280px;">Acciones</th>
            </tr>
          </thead>
          <tbody>
            @forelse($users as $u)
              @php
                $role = $u['role'] ?? '';
                $isActive = (bool)($u['is_active'] ?? false);
                $isAdmin = ($role === 'ADMIN');
              @endphp
              <tr>
                <td>#{{ $u['id'] ?? '-' }}</td>
                <td><strong>{{ $u['username'] ?? '-' }}</strong></td>
                <td>{{ $u['email'] ?? '-' }}</td>
                <td>
                  @if($isAdmin)
                    <span class="badge b-admin">ADMIN</span>
                  @else
                    <span class="badge b-user">USER</span>
                  @endif
                </td>
                <td>
                  @if($isActive)
                    <span class="badge b-active">ACTIVO</span>
                  @else
                    <span class="badge b-inactive">INACTIVO</span>
                  @endif
                </td>
                <td>
  @if($isAdmin)
    <span class="muted">Protegido</span>
  @else

    <div class="actions-wrap">
      <form method="POST" action="{{ route('users.toggle', $u['id']) }}" style="display:inline;">
        @csrf
        @method('PATCH')
        <button class="btnx btnx-soft" type="submit">
          {{ $isActive ? 'Desactivar' : 'Activar' }}
        </button>
      </form>

      <button
        class="btnx btnx-danger"
        type="button"
        data-open-delete="1"
        data-user-id="{{ $u['id'] }}"
        data-username="{{ $u['username'] ?? '' }}"
        data-email="{{ $u['email'] ?? '' }}"
        data-role="{{ $role }}"
        data-active="{{ $isActive ? '1' : '0' }}"
      >
        Eliminar
      </button>

      {{-- ✅ RESET PASSWORD --}}
      <button
        class="btnx btnx-soft"
        type="button"
        data-open-reset="1"
        data-user-id="{{ $u['id'] }}"
        data-username="{{ $u['username'] ?? '' }}"
        data-email="{{ $u['email'] ?? '' }}"
      >
        Reset contraseña
      </button>
    </div>

    {{-- Form DELETE real (lo dispara el modal) --}}
    <form method="POST" action="{{ route('users.destroy', $u['id']) }}"
          id="deleteForm-{{ $u['id'] }}" style="display:none;">
      @csrf
      @method('DELETE')
    </form>

    {{-- Form RESET real (lo dispara el modal) --}}
    <form method="POST" action="{{ route('users.reset-password', $u['id']) }}"
          id="resetForm-{{ $u['id'] }}" style="display:none;">
      @csrf
    </form>

  @endif
</td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="muted">No hay usuarios.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

{{-- Modal elegante con detalles (DELETE) --}}
<div class="modalx-backdrop" id="deleteModal">
  <div class="modalx" role="dialog" aria-modal="true" aria-labelledby="deleteTitle">
    <div class="modalx-head">
      <h3 class="modalx-title" id="deleteTitle">Confirmación con detalles adicionales</h3>
      <div class="muted" id="deleteHint" style="margin-top:.25rem;">
        Revisa la información. Si el cobrador tiene historial de pagos, el sistema bloqueará el borrado.
      </div>
    </div>

    <div class="modalx-body">
      <div>Vas a intentar eliminar definitivamente este cobrador:</div>

      <div class="kv" style="margin-top:.9rem;">
        <div>ID</div><div id="mId">-</div>
        <div>Username</div><div id="mUser">-</div>
        <div>Email</div><div id="mEmail">-</div>
        <div>Rol</div><div id="mRole">-</div>
        <div>Estado</div><div id="mActive">-</div>
      </div>

      <div style="margin-top:1rem; padding:.75rem .9rem; border-radius:12px; border:1px solid rgba(240,120,32,.22); background: rgba(240,120,32,.06); color:#7a4a12;">
        <strong>Regla banco:</strong> si tiene pagos ligados, no se borra. Solo se desactiva.
      </div>
    </div>

    <div class="modalx-foot">
      <button class="btnx btnx-soft" type="button" id="cancelDelete">Cancelar</button>
      <button class="btnx btnx-danger" type="button" id="confirmDelete">Eliminar definitivamente</button>
    </div>
  </div>
</div>

{{-- ✅ Modal RESET Password (confirmación con detalles) --}}
<div class="modalx-backdrop" id="resetModal">
  <div class="modalx" role="dialog" aria-modal="true" aria-labelledby="resetTitle">
    <div class="modalx-head">
      <h3 class="modalx-title" id="resetTitle">Confirmación con detalles adicionales</h3>
      <div class="muted" style="margin-top:.25rem;">
        Se generará una contraseña temporal para este cobrador. Entrégala de forma segura.
      </div>
    </div>

    <div class="modalx-body">
      <div>Vas a resetear contraseña de:</div>

      <div class="kv" style="margin-top:.9rem;">
        <div>ID</div><div id="rId">-</div>
        <div>Username</div><div id="rUser">-</div>
        <div>Email</div><div id="rEmail">-</div>
      </div>

      <div style="margin-top:1rem; padding:.75rem .9rem; border-radius:12px; border:1px solid rgba(26,111,207,.22); background: rgba(26,111,207,.06); color:#0b3c77;">
        <strong>Regla banco:</strong> la contraseña anterior dejará de funcionar inmediatamente.
      </div>
    </div>

    <div class="modalx-foot">
      <button class="btnx btnx-soft" type="button" id="cancelReset">Cancelar</button>
      <button class="btnx btnx-primary" type="button" id="confirmReset">Generar contraseña temporal</button>
    </div>
  </div>
</div>

{{-- ✅ Modal Resultado RESET (muestra temp_password) --}}
<div class="modalx-backdrop" id="resetResultModal" style="display:none;">
  <div class="modalx" role="dialog" aria-modal="true" aria-labelledby="resetResultTitle">
    <div class="modalx-head">
      <h3 class="modalx-title" id="resetResultTitle">Contraseña temporal generada</h3>
      <div class="muted" style="margin-top:.25rem;">
        Copia y entrega esta contraseña al cobrador. Recomienda cambiarla en el primer acceso.
      </div>
    </div>

    <div class="modalx-body">
      @php($rr = $resetResult ?? null)

      <div class="kv" style="margin-top:.2rem;">
        <div>Usuario</div><div><strong>{{ $rr['username'] ?? '-' }}</strong></div>
        <div>ID</div><div>#{{ $rr['user_id'] ?? '-' }}</div>
      </div>

      <div style="margin-top:1rem;">
        <div class="field-label">Contraseña temporal</div>
        <div style="display:flex; gap:.6rem; align-items:center; flex-wrap:wrap;">
          <input class="field-input" id="tempPass" type="text" readonly
                 value="{{ $rr['temp_password'] ?? '' }}" style="max-width: 360px;">
          <button class="btnx btnx-soft" type="button" id="copyTempPass">Copiar</button>
        </div>
        <div class="muted" id="copyMsg" style="margin-top:.4rem; display:none;">Copiado ✅</div>
      </div>
    </div>

    <div class="modalx-foot">
      <button class="btnx btnx-primary" type="button" id="closeResetResult">Listo</button>
    </div>
  </div>
</div>

@push('scripts')
<script>
(function(){
  // ====== limpiar form ======
  var clearUserForm = @json((bool)($clearUserForm ?? false));
  var form = document.getElementById('createUserForm');
  var clearBtn = document.getElementById('clearFormBtn');

  function clearForm(){
    if(!form) return;
    form.reset();
    // dispara validación live
    updateRules("");
    syncCreateButton();
  }

  if(clearBtn){
    clearBtn.addEventListener('click', clearForm);
  }

  // Si venimos de creado OK, limpiar inputs (nivel banco)
  if(clearUserForm){
    clearForm();
  }

  // ====== ojo único ======
  var passInput = document.getElementById('password');
  var toggleBtn = document.getElementById('togglePass');
  var eyeIcon   = document.getElementById('eyeIcon');

  if(toggleBtn && passInput){
    toggleBtn.addEventListener('click', function(){
      var show = (passInput.type === 'password');
      passInput.type = show ? 'text' : 'password';
      eyeIcon.innerHTML = show
        ? '<path stroke-linecap="round" stroke-linejoin="round" d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24M1 1l22 22"/>'
        : '<path stroke-linecap="round" stroke-linejoin="round" d="M1 12S5 4 12 4s11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/>';
    });

    // quita el icono automático del navegador (Edge/IE)
    passInput.style.msReveal = "none";
  }

  // ====== validación live password ======
  var rulesBox = document.getElementById('passRules');

  function setRule(name, ok){
    if(!rulesBox) return;
    var el = rulesBox.querySelector('[data-rule="'+name+'"]');
    if(!el) return;
    if(ok) el.classList.add('ok'); else el.classList.remove('ok');
  }

  function rulesState(val){
    val = val || "";
    return {
      len:   val.length >= 8,
      upper: /[A-Z]/.test(val),
      num:   /[0-9]/.test(val),
      spec:  /[^A-Za-z0-9]/.test(val)
    };
  }

  function updateRules(val){
    var st = rulesState(val || "");
    setRule('len',   st.len);
    setRule('upper', st.upper);
    setRule('num',   st.num);
    setRule('spec',  st.spec);
    return st;
  }

  // ✅ Bloquear creación si no cumple TODO
  var submitBtn = document.getElementById('createSubmitBtn');
  var usernameInput = document.getElementById('username');
  var emailInput = document.getElementById('email');

  function syncCreateButton(){
    if(!submitBtn) return;

    var pass = passInput ? (passInput.value || "") : "";
    var st = updateRules(pass);

    var userOk = usernameInput ? (usernameInput.value || "").trim().length >= 3 : false;
    var emailOk = emailInput ? (emailInput.value || "").trim().length > 0 : false;

    var passOk = st.len && st.upper && st.num && st.spec;

    var ok = userOk && emailOk && passOk;

    submitBtn.disabled = !ok;
    submitBtn.style.opacity = ok ? "1" : ".55";
    submitBtn.style.cursor = ok ? "pointer" : "not-allowed";
  }

  if(passInput){
    updateRules(passInput.value || "");
    passInput.addEventListener('input', function(e){
      updateRules(e.target.value);
      syncCreateButton();
    });
  }
  if(usernameInput) usernameInput.addEventListener('input', syncCreateButton);
  if(emailInput) emailInput.addEventListener('input', syncCreateButton);

  // Primer sync al cargar
  syncCreateButton();

  // Si alguien forza submit con Enter, bloquear igual
  if(form){
    form.addEventListener('submit', function(e){
      syncCreateButton();
      if(submitBtn && submitBtn.disabled){
        e.preventDefault();
      }
    });
  }

  // ====== modal eliminar (sin confirm()) ======
  var modal = document.getElementById('deleteModal');
  var cancel = document.getElementById('cancelDelete');
  var confirm = document.getElementById('confirmDelete');

  var mId = document.getElementById('mId');
  var mUser = document.getElementById('mUser');
  var mEmail = document.getElementById('mEmail');
  var mRole = document.getElementById('mRole');
  var mActive = document.getElementById('mActive');

  var currentDeleteId = null;

  function openModal(data){
    currentDeleteId = data.id;

    mId.textContent = '#'+data.id;
    mUser.textContent = data.username || '-';
    mEmail.textContent = data.email || '-';
    mRole.textContent = data.role || '-';
    mActive.textContent = (data.active === '1') ? 'ACTIVO' : 'INACTIVO';

    modal.style.display = 'flex';
  }

  function closeModal(){
    modal.style.display = 'none';
    currentDeleteId = null;
  }

  document.querySelectorAll('[data-open-delete="1"]').forEach(function(btn){
    btn.addEventListener('click', function(){
      openModal({
        id: btn.getAttribute('data-user-id'),
        username: btn.getAttribute('data-username'),
        email: btn.getAttribute('data-email'),
        role: btn.getAttribute('data-role'),
        active: btn.getAttribute('data-active'),
      });
    });
  });

  if(cancel) cancel.addEventListener('click', closeModal);
  if(modal) modal.addEventListener('click', function(e){
    if(e.target === modal) closeModal();
  });

  if(confirm){
    confirm.addEventListener('click', function(){
      if(!currentDeleteId) return;

      var formId = 'deleteForm-' + currentDeleteId;
      var f = document.getElementById(formId);
      if(f) f.submit();
    });
  }

  // ====== modal reset password ======
  var resetModal = document.getElementById('resetModal');
  var cancelReset = document.getElementById('cancelReset');
  var confirmReset = document.getElementById('confirmReset');

  var rId = document.getElementById('rId');
  var rUser = document.getElementById('rUser');
  var rEmail = document.getElementById('rEmail');

  var currentResetId = null;

  function openResetModal(data){
    currentResetId = data.id;
    rId.textContent = '#'+data.id;
    rUser.textContent = data.username || '-';
    rEmail.textContent = data.email || '-';
    resetModal.style.display = 'flex';
  }

  function closeResetModal(){
    resetModal.style.display = 'none';
    currentResetId = null;
  }

  document.querySelectorAll('[data-open-reset="1"]').forEach(function(btn){
    btn.addEventListener('click', function(){
      openResetModal({
        id: btn.getAttribute('data-user-id'),
        username: btn.getAttribute('data-username'),
        email: btn.getAttribute('data-email'),
      });
    });
  });

  if(cancelReset) cancelReset.addEventListener('click', closeResetModal);
  if(resetModal) resetModal.addEventListener('click', function(e){
    if(e.target === resetModal) closeResetModal();
  });

  if(confirmReset){
    confirmReset.addEventListener('click', function(){
      if(!currentResetId) return;
      var f = document.getElementById('resetForm-' + currentResetId);
      if(f) f.submit();
    });
  }

  // ====== mostrar modal resultado reset si viene de session(reset_result) ======
  var hasResetResult = @json((bool)($resetResult ?? false));
  var resetResultModal = document.getElementById('resetResultModal');
  var closeResetResult = document.getElementById('closeResetResult');
  var copyBtn = document.getElementById('copyTempPass');
  var tempPass = document.getElementById('tempPass');
  var copyMsg = document.getElementById('copyMsg');

  function closeResetResultModal(){
    if(resetResultModal) resetResultModal.style.display = 'none';
  }

  if(hasResetResult && resetResultModal){
    resetResultModal.style.display = 'flex';
  }

  if(closeResetResult) closeResetResult.addEventListener('click', closeResetResultModal);
  if(resetResultModal) resetResultModal.addEventListener('click', function(e){
    if(e.target === resetResultModal) closeResetResultModal();
  });

  if(copyBtn && tempPass){
    copyBtn.addEventListener('click', async function(){
      try {
        await navigator.clipboard.writeText(tempPass.value || '');
        if(copyMsg){
          copyMsg.style.display = 'block';
          setTimeout(function(){ copyMsg.style.display = 'none'; }, 1400);
        }
      } catch(e) {
        tempPass.select();
        document.execCommand('copy');
        if(copyMsg){
          copyMsg.style.display = 'block';
          setTimeout(function(){ copyMsg.style.display = 'none'; }, 1400);
        }
      }
    });
  }
})();
</script>
@endpush
@endsection









