@extends('layouts.guest')

@section('title', 'Login - MYBANK')

{{-- ================================================================
     LOGIN — resources/views/auth/login.blade.php
     Requiere: app.css (estilos globales con variables Accionex)
     El <style> de este archivo sólo agrega componentes exclusivos
     del login que no existen en app.css
================================================================ --}}

@push('styles')
<style>
/* ── Auth layout ── */
.auth-wrap {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
  position: relative;
  overflow: hidden;
  background:
    radial-gradient(ellipse 900px 600px at -10% 110%, rgba(18,169,138,.12) 0%, transparent 60%),
    radial-gradient(ellipse 700px 500px at 110%  -5%, rgba(26,111,207,.13) 0%, transparent 55%),
    radial-gradient(ellipse 500px 400px at  95% 100%, rgba(240,120,32,.09) 0%, transparent 55%),
    linear-gradient(160deg, #e8f0fb 0%, #f0f6fc 50%, #e9f8f3 100%);
}

.auth-shell {
  width: min(960px, 100%);
  display: grid;
  grid-template-columns: 1.1fr .9fr;
  border-radius: 22px;
  box-shadow: 0 24px 64px rgba(13,27,46,.14), 0 4px 16px rgba(26,111,207,.10);
  overflow: hidden;
  position: relative;
  z-index: 1;
  animation: shellIn .5s cubic-bezier(.22,1,.36,1) both;
}
@keyframes shellIn {
  from { opacity:0; transform:translateY(22px) scale(.98); }
  to   { opacity:1; transform:translateY(0)    scale(1);   }
}

/* ── Hero left panel ── */
.auth-hero {
  background:
    radial-gradient(ellipse 700px 500px at 0%   0%,  rgba(26,111,207,.85) 0%, transparent 55%),
    radial-gradient(ellipse 600px 600px at 100% 70%, rgba(18,169,138,.80) 0%, transparent 55%),
    radial-gradient(ellipse 400px 300px at 50%  50%, rgba(240,120,32,.25) 0%, transparent 60%),
    linear-gradient(150deg, #0e4fa8 0%, #0e6a56 80%);
  padding: 2.5rem 2.25rem;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  position: relative;
  overflow: hidden;
  color: #fff;
  min-height: 540px;
}
.auth-hero::before {
  content: '';
  position: absolute; inset: 0;
  background-image:
    linear-gradient(rgba(255,255,255,.04) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,.04) 1px, transparent 1px);
  background-size: 32px 32px;
  pointer-events: none;
}

.hero-arc {
  position: absolute;
  border-radius: 999px;
  border: 1px solid rgba(255,255,255,.12);
  pointer-events: none;
}
.hero-arc-1 { width:320px; height:320px; top:-80px;  right:-80px; }
.hero-arc-2 { width:200px; height:200px; bottom:60px; left:-40px; }
.hero-arc-3 { width:140px; height:140px; bottom:40px; left: 20px; }

/* Logo Accionex */
.hero-logo-wrap {
  display: flex;
  align-items: center;
  gap: .85rem;
  position: relative;
  z-index: 1;
}
.hero-logo-img {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  object-fit: cover;
  border: 2.5px solid rgba(255,255,255,.55);
  box-shadow: 0 4px 20px rgba(0,0,0,.25), 0 0 0 6px rgba(255,255,255,.10);
  flex-shrink: 0;
  background: #fff;
}
.hero-logo-text { display: flex; flex-direction: column; }
.hero-brand-name {
  font-size: 1.3rem;
  font-weight: 900;
  letter-spacing: .12em;
  line-height: 1.1;
  opacity: .97;
}
.hero-brand-sub {
  font-size: .80rem;
  opacity: .72;
  letter-spacing: .06em;
  margin-top: .1rem;
}

.hero-body  { position: relative; z-index: 1; }
.hero-title {
  font-size: clamp(1.85rem, 3vw, 2.5rem);
  font-weight: 900;
  letter-spacing: -.02em;
  line-height: 1.18;
  margin: 0 0 .75rem 0;
}
.hero-sub  { font-size: 1rem; opacity: .78; line-height: 1.6; margin: 0 0 1.5rem 0; }

.hero-features { display: flex; flex-direction: column; gap: .65rem; }
.hero-feat {
  display: flex; align-items: center; gap: .65rem;
  padding: .65rem .85rem;
  border-radius: 12px;
  background: rgba(255,255,255,.10);
  border: 1px solid rgba(255,255,255,.14);
  font-size: .90rem; font-weight: 600;
}
.feat-dot { width:8px; height:8px; border-radius:999px; flex-shrink:0; }

.hero-footer {
  position: relative; z-index: 1;
  border-top: 1px solid rgba(255,255,255,.14);
  padding-top: 1rem;
  font-size: .82rem; opacity: .65; letter-spacing: .04em;
}

/* ── Form right panel ── */
.auth-form-side {
  background: #fff;
  padding: 0 2.25rem 2.5rem 2.25rem;
  display: flex; flex-direction: column; justify-content: center;
}

.form-accent-bar {
  height: 4px;
  background: linear-gradient(90deg,
    #1a6fcf 0%, #12a98a 25%, #23b85b 50%,
    #f07820 72%, #e03a3a 88%, #8b3fc8 100%);
  margin: 0 -2.25rem 2rem -2.25rem;
}

.form-head     { margin-bottom: 1.5rem; }
.form-title    { font-size: 1.65rem; font-weight: 900; letter-spacing: -.02em; color: #0d1b2e; margin: 0 0 .3rem 0; }
.form-sub      { color: #6b7e96; font-size: .93rem; margin: 0; }

/* Alert */
.alert-acx {
  background: rgba(224,58,58,.07);
  border: 1px solid rgba(224,58,58,.22);
  border-radius: 12px;
  color: #c02020;
  padding: .75rem 1rem;
  font-size: .90rem; font-weight: 600;
  display: flex; align-items: center; gap: .5rem;
  margin-bottom: 1.25rem;
}

/* Fields */
.field-group   { margin-bottom: 1.1rem; }
.field-label   { display: block; font-weight: 700; font-size: .88rem; color: #3a4d65; margin-bottom: .45rem; }
.field-wrap    { position: relative; display: flex; align-items: center; }

.field-icon    {
  position: absolute; left: .9rem;
  width: 20px; height: 20px;
  color: #6b7e96; flex-shrink: 0; pointer-events: none;
}

.field-input {
  width: 100%;
  padding: .80rem 1rem .80rem 2.75rem;
  border: 1.5px solid rgba(26,111,207,.16);
  border-radius: 12px;
  background: #f8faff;
  font-family: 'Outfit', sans-serif;
  font-size: .97rem; color: #0d1b2e;
  outline: none;
  transition: border-color .18s, box-shadow .18s, background .18s;
  -ms-reveal: none;
}
.field-input::-ms-reveal,
.field-input::-ms-clear { display: none; }
.field-input:-webkit-autofill {
  -webkit-box-shadow: 0 0 0 100px #f8faff inset !important;
  -webkit-text-fill-color: #0d1b2e !important;
}
.field-input::placeholder { color: #6b7e96; }
.field-input:focus {
  border-color: #1a6fcf;
  background: #fff;
  box-shadow: 0 0 0 3px rgba(26,111,207,.12);
}
.field-input--pass { padding-right: 3rem; }

/* Eye toggle */
.field-eye {
  position: absolute; right: .7rem;
  background: transparent; border: none;
  cursor: pointer; padding: .3rem;
  border-radius: 8px;
  color: #6b7e96;
  display: flex; align-items: center;
  transition: color .15s;
  line-height: 1;
}
.field-eye:hover { color: #1a6fcf; }

/* Footer row */
.form-foot-row {
  display: flex; align-items: center; justify-content: space-between;
  margin: 1rem 0 1.4rem 0; gap: 1rem;
}
.remember-label {
  display: flex; align-items: center; gap: .5rem;
  cursor: pointer; user-select: none;
  font-size: .88rem; color: #3a4d65; font-weight: 600;
}
.remember-check {
  width: 17px; height: 17px;
  border: 1.5px solid rgba(26,111,207,.30);
  border-radius: 5px; appearance: none;
  cursor: pointer; position: relative;
  background: #fff;
  transition: background .15s, border-color .15s;
  flex-shrink: 0;
}
.remember-check:checked { background: #1a6fcf; border-color: #1a6fcf; }
.remember-check:checked::after {
  content: ''; position: absolute;
  left: 4px; top: 1px; width: 5px; height: 9px;
  border: 2px solid #fff; border-top: 0; border-left: 0;
  transform: rotate(42deg);
}
.form-version { font-size: .80rem; color: #6b7e96; font-weight: 500; white-space: nowrap; }

/* Submit button */
.btn-submit {
  width: 100%; padding: .88rem 1rem;
  border: none; border-radius: 12px;
  background: linear-gradient(135deg, #1a6fcf 0%, #1259b0 100%);
  color: #fff; font-family: 'Outfit', sans-serif;
  font-size: 1rem; font-weight: 800; letter-spacing: .03em;
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  gap: .55rem;
  transition: filter .18s, transform .12s, box-shadow .18s;
  box-shadow: 0 4px 20px rgba(26,111,207,.30);
  position: relative; overflow: hidden;
}
.btn-submit::after {
  content: ''; position: absolute; inset: 0;
  background: linear-gradient(to bottom, rgba(255,255,255,.12), transparent);
  pointer-events: none;
}
.btn-submit:hover:not(:disabled) {
  filter: brightness(1.07);
  box-shadow: 0 6px 26px rgba(26,111,207,.40);
  transform: translateY(-1px);
}
.btn-submit:active:not(:disabled) { transform: translateY(0); }
.btn-submit:disabled { opacity: .7; cursor: not-allowed; }

.btn-spinner {
  width: 16px; height: 16px;
  border: 2px solid rgba(255,255,255,.4);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin .65s linear infinite;
  display: none;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Security note */
.sec-note {
  display: flex; align-items: center; justify-content: center;
  gap: .45rem; margin-top: 1.1rem;
  font-size: .82rem; color: #6b7e96; font-weight: 500;
}
.sec-note svg { color: #12a98a; }

/* Responsive */
@media (max-width: 780px) {
  .auth-shell            { grid-template-columns: 1fr; }
  .auth-hero             { display: none; }
  .auth-form-side        { padding: 0 1.5rem 2rem 1.5rem; }
}
</style>
@endpush

{{-- ✅ AQUÍ ESTABA EL ERROR EN TU ARCHIVO: faltaba abrir content --}}
@section('content')

<div class="auth-wrap">
  <div class="auth-shell">

    <!-- LEFT HERO -->
    <div class="auth-hero">
      <div class="hero-arc hero-arc-1"></div>
      <div class="hero-arc hero-arc-2"></div>
      <div class="hero-arc hero-arc-3"></div>

      <div class="hero-logo-wrap">
        <img
          src="data:image/jpeg;base64,PEGA_AQUI_TU_BASE64_EXISTENTE_SIN_CAMBIARLO"
          alt="Accionex"
          class="hero-logo-img"
        >
        <div class="hero-logo-text">
          <span class="hero-brand-name">MYBANK</span>
          <span class="hero-brand-sub">Portal Administrativo</span>
        </div>
      </div>

      <div class="hero-body">
        <h1 class="hero-title">Gesti&oacute;n financiera en un solo lugar</h1>
        <p class="hero-sub">Acceso seguro al panel de control para administradores autorizados de Acciones.</p>
        <div class="hero-features">
          <div class="hero-feat"><span class="feat-dot" style="background:#12a98a"></span>Monitoreo de cuentas en tiempo real</div>
          <div class="hero-feat"><span class="feat-dot" style="background:#f07820"></span>Gesti&oacute;n de usuarios y permisos</div>
          <div class="hero-feat"><span class="feat-dot" style="background:#1a6fcf"></span>Reportes y auditor&iacute;a completa</div>
        </div>
      </div>

      <div class="hero-footer">
        &copy; {{ date('Y') }} Acciones &middot; Todos los derechos reservados
      </div>
    </div>

    <!-- RIGHT FORM -->
    <div class="auth-form-side">
      <div class="form-accent-bar"></div>

      <div class="form-head">
        <h2 class="form-title">Iniciar sesi&oacute;n</h2>
        <p class="form-sub">Ingresa tus credenciales de administrador para continuar.</p>
      </div>

      @if($errors->any())
        <div class="alert-acx">
          <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          {{ $errors->first('login') ?? 'Credenciales inv&aacute;lidas. Intenta de nuevo.' }}
        </div>
      @endif

      <form id="loginForm" method="POST" action="{{ route('login.post') }}">
        @csrf

        <!-- Usuario -->
        <div class="field-group">
          <label class="field-label" for="username">Usuario</label>
          <div class="field-wrap">
            <svg class="field-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2M12 11a4 4 0 100-8 4 4 0 000 8z"/>
            </svg>
            <input
              id="username" name="username" type="text"
              class="field-input"
              placeholder="admin"
              autocomplete="username"
              required
            >
          </div>
        </div>

        <!-- Contraseña -->
        <div class="field-group">
          <label class="field-label" for="password">Contrase&ntilde;a</label>
          <div class="field-wrap">
            <svg class="field-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M7 11V7a5 5 0 0110 0v4"/>
            </svg>
            <input
              id="password" name="password" type="password"
              class="field-input field-input--pass"
              placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
              autocomplete="current-password"
              required
            >
            <button type="button" class="field-eye" id="togglePassword" aria-label="Mostrar contrase&ntilde;a">
              <svg id="eyeIcon" width="18" height="18" fill="none" viewBox="0 0 24 24"
                   stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M1 12S5 4 12 4s11 8 11 8-4 8-11 8S1 12 1 12z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
        </div>

        <div class="form-foot-row">
          <label class="remember-label">
            <input type="checkbox" class="remember-check" id="rememberUser">
            Recordar usuario
          </label>
          <span class="form-version">MYBANK Web Admin</span>
        </div>

        <button id="loginBtn" class="btn-submit" type="submit">
          <span id="btnLabel">Entrar al sistema</span>
          <div class="btn-spinner" id="btnSpinner"></div>
          <svg id="btnArrow" width="16" height="16" fill="none" viewBox="0 0 24 24"
               stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M12 5l7 7-7 7"/>
          </svg>
        </button>

        <div class="sec-note">
          <svg width="13" height="13" fill="none" viewBox="0 0 24 24"
               stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
          </svg>
          Conexi&oacute;n cifrada SSL &middot; Sesi&oacute;n segura
        </div>
      </form>
    </div>

  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
  var passwordInput = document.getElementById("password");
  var toggleBtn     = document.getElementById("togglePassword");
  var eyeIcon       = document.getElementById("eyeIcon");
  var usernameInput = document.getElementById("username");
  var rememberCheck = document.getElementById("rememberUser");
  var form          = document.getElementById("loginForm");
  var loginBtn      = document.getElementById("loginBtn");
  var btnLabel      = document.getElementById("btnLabel");
  var btnSpinner    = document.getElementById("btnSpinner");
  var btnArrow      = document.getElementById("btnArrow");

  toggleBtn.addEventListener("click", function () {
    var show = (passwordInput.type === "password");
    passwordInput.type = show ? "text" : "password";
    eyeIcon.innerHTML = show
      ? '<path stroke-linecap="round" stroke-linejoin="round" d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24M1 1l22 22"/>'
      : '<path stroke-linecap="round" stroke-linejoin="round" d="M1 12S5 4 12 4s11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/>';
  });

  var saved = localStorage.getItem("mybank_user");
  if (saved) { usernameInput.value = saved; rememberCheck.checked = true; }

  form.addEventListener("submit", function () {
    loginBtn.disabled        = true;
    btnLabel.textContent     = "Verificando...";
    btnSpinner.style.display = "block";
    btnArrow.style.display   = "none";

    if (rememberCheck.checked) {
      localStorage.setItem("mybank_user", usernameInput.value);
    } else {
      localStorage.removeItem("mybank_user");
    }
  });
});
</script>

@endsection
