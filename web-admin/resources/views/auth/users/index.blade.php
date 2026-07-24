@extends('layouts.app')

@section('title', 'Usuarios (Cobradores) - MYBANK')

@push('styles')
<style>
  /* ── Estilos específicos de la página Usuarios ── */
  .pass-wrap { position: relative; }
  .pass-eye {
    position: absolute;
    right: .65rem;
    top: 50%;
    transform: translateY(-50%);
    border: 0;
    background: transparent;
    padding: .35rem;
    border-radius: 10px;
    cursor: pointer;
    color: #6b7e96;
    transition: color .15s;
  }
  .pass-eye:hover { color: #1a6fcf; }

  /* Reglas de contraseña en tiempo real */
  .pass-rules {
    margin-top: .65rem;
    border: 1px dashed rgba(26,111,207,.18);
    background: rgba(26,111,207,.04);
    border-radius: 12px;
    padding: .75rem .9rem;
    display: flex;
    flex-direction: column;
    gap: .35rem;
    font-size: .88rem;
    color: #3a4d65;
  }
  .rule { display: flex; align-items: center; gap: .5rem; }
  .dot {
    width: 10px; height: 10px; border-radius: 999px;
    background: rgba(224,58,58,.70);
    box-shadow: 0 0 0 4px rgba(224,58,58,.10);
    flex-shrink: 0;
    transition: background .2s, box-shadow .2s;
  }
  .rule.ok .dot {
    background: rgba(18,169,138,.95);
    box-shadow: 0 0 0 4px rgba(18,169,138,.12);
  }

  /* Ocultar reveal nativo del navegador */
  input[type="password"]::-ms-reveal,
  input[type="password"]::-ms-clear { display: none; width: 0; height: 0; }
  input[type="password"]::-webkit-credentials-auto-fill-button {
    visibility: hidden; display: none !important;
    pointer-events: none; position: absolute; right: 0;
  }
</style>
@endpush

@section('content')
<div class="page-header">
  <div>
    <div class="breadcrumb">
      <a href="{{ route('dashboard') }}">Dashboard</a>
      <span class="breadcrumb-sep">›</span>
      Usuarios
    </div>
    <h1 class="page-title">Usuarios (Cobradores)</h1>
    <p class="page-sub">Crea y administra los cobradores que iniciarán sesión en la aplicación móvil.</p>
  </div>
  <div>
    <button class="btn btn-primary" onclick="openDrawer('registerUserDrawer')">
      <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
        <path stroke-linecap="round" d="M12 5v14M5 12h14"/>
      </svg>
      Nuevo Cobrador
    </button>
  </div>
</div>

<div class="row g-3">
  <div class="col-12">
    <div class="card" style="overflow:hidden;">
      <div class="card-head">
        <h3 class="card-title">Lista de usuarios registrados</h3>
        <span style="font-size:.84rem;color:var(--muted); font-weight: 600;">{{ count($users) }} usuario(s)</span>
      </div>

      <div class="card-body-flush">
        <div class="table-wrap">
          <table class="tbl">
            <thead>
              <tr>
                <th style="width:120px;">ID</th>
                <th>Username</th>
                <th>Correo electrónico</th>
                <th style="width:140px;">Rol</th>
                <th style="width:140px;">Estado</th>
                <th style="width:70px; text-align:center;">Acciones</th>
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
                  <td class="mono">#{{ $u['user_number'] ?? ($u['id'] ?? '-') }}</td>
                  <td><strong>{{ $u['username'] ?? '-' }}</strong></td>
                  <td>{{ $u['email'] ?? '-' }}</td>
                  <td>
                    @if($isAdmin)
                      <span class="badge badge-blue"><span class="badge-dot"></span>ADMIN</span>
                    @else
                      <span class="badge badge-green"><span class="badge-dot"></span>USER</span>
                    @endif
                  </td>
                  <td>
                    @if($isActive)
                      <span class="badge badge-teal"><span class="badge-dot"></span>ACTIVO</span>
                    @else
                      <span class="badge badge-red"><span class="badge-dot"></span>INACTIVO</span>
                    @endif
                  </td>
                  <td style="text-align:center;">
                    @if($isAdmin)
                      <span class="cell-muted" style="font-size:0.82rem;">Protegido</span>
                    @else
                      <div class="meatball-menu">
                        <button class="meatball-btn" type="button" aria-label="Acciones">
                          <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                          </svg>
                        </button>
                        <div class="meatball-dropdown">
                          {{-- Toggle Active status --}}
                          <form method="POST" action="{{ route('users.toggle', $u['id']) }}" style="display:inline;">
                            @csrf
                            @method('PATCH')
                            <button class="meatball-item btn-confirm" type="submit" data-confirm-text="{{ $isActive ? '¿Desactivar?' : '¿Activar?' }}">
                              <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636"/>
                              </svg>
                              {{ $isActive ? 'Desactivar' : 'Activar' }}
                            </button>
                          </form>

                          {{-- Reset Password --}}
                          <form method="POST" action="{{ route('users.reset-password', $u['id']) }}" style="display:inline;">
                            @csrf
                            <button class="meatball-item btn-confirm" type="submit" data-confirm-text="¿Resetear clave?">
                              <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" d="M15 7a2 2 0 012 2m-2 4a2 2 0 012 2m3-3a3 3 0 11-6 0 3 3 0 016 0zm-7 0H3"/>
                              </svg>
                              Reset contraseña
                            </button>
                          </form>

                          {{-- Delete User --}}
                          <form method="POST" action="{{ route('users.destroy', $u['id']) }}" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button class="meatball-item text-danger btn-confirm" type="submit" data-confirm-text="¿Confirmar borrar?">
                              <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"/><path stroke-linecap="round" d="M19 6l-1 14H6L5 6M10 11v6M14 11v6M9 6V4h6v2"/>
                              </svg>
                              Eliminar
                            </button>
                          </form>
                        </div>
                      </div>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="cell-muted" style="text-align:center; padding:3rem;">No hay usuarios cobradores registrados.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ── DRAWER: NUEVO COBRADOR ── --}}
<div class="drawer" id="registerUserDrawer">
  <div class="drawer-head">
    <h3 class="drawer-title">
      <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
        <path stroke-linecap="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3M9 11a4 4 0 100-8 4 4 0 000 8zm-6 9a6 6 0 0112 0v1H3v-1z"/>
      </svg>
      Crear cobrador (USER)
    </h3>
    <button class="drawer-close" onclick="closeDrawer('registerUserDrawer')">✕</button>
  </div>
  <form method="POST" action="{{ route('users.store') }}" id="createUserForm" autocomplete="off">
    @csrf
    <div class="drawer-body">
      <p style="font-size:0.86rem; color:var(--muted); margin-bottom:1.25rem;">
        Estos datos serán los mismos para iniciar sesión en la aplicación móvil de cobros.
      </p>

      <div class="field">
        <label class="field-label" for="username">Username</label>
        <input class="field-input" id="username" name="username" type="text"
               value="{{ old('username') }}" placeholder="ej. cobrador01" required>
      </div>

      <div class="field">
        <label class="field-label" for="email">Correo electrónico</label>
        <input class="field-input" id="email" name="email" type="email"
               value="{{ old('email') }}" placeholder="ej. cobrador@dominio.com" required>
      </div>

      <div class="field">
        <label class="field-label" for="password">Contraseña</label>
        <div class="pass-wrap">
          <input class="field-input" id="password" name="password" type="password"
                 placeholder="Mínimo 8, mayúscula, número, especial" required
                 autocomplete="new-password" style="padding-right:3rem;">
          <button type="button" class="pass-eye" id="togglePass" aria-label="Mostrar contraseña">
            <svg id="eyeIcon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M1 12S5 4 12 4s11 8 11 8-4 8-11 8S1 12 1 12z"/>
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
      </div>
    </div>
    <div class="drawer-foot">
      <button class="btn btn-ghost" type="button" onclick="closeDrawer('registerUserDrawer')">Cancelar</button>
      <button class="btn btn-primary" type="submit" id="createSubmitBtn">Crear cobrador</button>
    </div>
  </form>
</div>

{{-- ── DRAWER: RESULTADO DEL RESET DE CONTRASEÑA ── --}}
@if($resetResult)
<div class="drawer open" id="resetResultDrawer">
  <div class="drawer-head">
    <h3 class="drawer-title">🔑 Contraseña temporal</h3>
    <button class="drawer-close" onclick="closeDrawer('resetResultDrawer')">✕</button>
  </div>
  <div class="drawer-body">
    <p style="font-size:.92rem; margin-bottom:1.2rem; color:var(--text-2);">
      Se ha generado una contraseña temporal para el cobrador. Cópiala y entrégala de forma segura.
    </p>

    <div class="card p-3 mb-3" style="background:#fafcff; border: 1.5px solid var(--line);">
      <div style="font-size: .80rem; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em;">Cobrador</div>
      <div style="font-size: 1.15rem; font-weight: 800; color: var(--text); margin-top: 4px;">{{ $resetResult['username'] ?? '-' }}</div>

      <div style="font-size: .80rem; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 12px;">ID Usuario</div>
      <div style="font-size: 1.15rem; font-weight: 800; color: var(--text); margin-top: 4px;">#{{ $resetResult['user_id'] ?? '-' }}</div>
    </div>

    <div class="field">
      <label class="field-label">Contraseña temporal</label>
      <div style="display:flex; gap:.6rem; align-items:center;">
        <input class="field-input" id="tempPass" type="text" readonly
               value="{{ $resetResult['temp_password'] ?? '' }}" style="font-family: monospace; font-weight: bold; font-size: 1.1rem; background:#ffffff; border-color:var(--teal); text-align:center; letter-spacing: 1px;">
        <button class="btn btn-teal" type="button" id="copyTempPass">Copiar</button>
      </div>
      <div class="text-teal font-weight-bold" id="copyMsg" style="margin-top:.5rem; display:none; font-size:0.84rem;">✓ ¡Contraseña copiada al portapapeles!</div>
    </div>
  </div>
  <div class="drawer-foot">
    <button class="btn btn-primary btn-full" type="button" onclick="closeDrawer('resetResultDrawer')">Listo</button>
  </div>
</div>
@endif

@endsection

@push('scripts')
<script>
(function(){
  // Clear user form utility
  var clearUserForm = @json((bool)($clearUserForm ?? false));
  var form = document.getElementById('createUserForm');

  function clearForm(){
    if(!form) return;
    form.reset();
    updateRules("");
    syncCreateButton();
  }

  // Clear if action succeeded
  if(clearUserForm){
    clearForm();
  }

  // Password eye toggle
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
    passInput.style.msReveal = "none";
  }

  // Live password validation
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

  syncCreateButton();

  if(form){
    form.addEventListener('submit', function(e){
      syncCreateButton();
      if(submitBtn && submitBtn.disabled){
        e.preventDefault();
      }
    });
  }

  // Copy temp password functionality
  var copyBtn = document.getElementById('copyTempPass');
  var tempPass = document.getElementById('tempPass');
  var copyMsg = document.getElementById('copyMsg');

  if(copyBtn && tempPass){
    copyBtn.addEventListener('click', async function(){
      try {
        await navigator.clipboard.writeText(tempPass.value || '');
        if(copyMsg){
          copyMsg.style.display = 'block';
          setTimeout(function(){ copyMsg.style.display = 'none'; }, 2000);
        }
      } catch(e) {
        tempPass.select();
        document.execCommand('copy');
        if(copyMsg){
          copyMsg.style.display = 'block';
          setTimeout(function(){ copyMsg.style.display = 'none'; }, 2000);
        }
      }
    });
  }
})();
</script>
@endpush
