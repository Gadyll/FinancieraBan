@extends('layouts.app')

@section('title', 'Usuarios (Cobradores) - MYBANK')

@section('content')
<div class="page">
  <div class="container">

    <div class="page-head">
      <div>
        <h1 class="page-title">Usuarios (Cobradores)</h1>
        <p class="page-sub">Crea y administra cobradores que usarán la app móvil.</p>
      </div>
    </div>

    @if(session('ok'))
      <div class="alert alert-success">{{ session('ok') }}</div>
    @endif

    @if($errors->has('users'))
      <div class="alert alert-danger">{{ $errors->first('users') }}</div>
    @endif

    @if(!empty($error))
      <div class="alert alert-danger">{{ $error }}</div>
    @endif

    <div class="surface">
      <div class="accent-bar"></div>
      <div class="surface-pad">

        {{-- FORM CREATE --}}
        <div class="row g-3 align-items-start">
          <div class="col-lg-4">
            <h5 class="mb-1" style="font-weight:900;">Crear cobrador</h5>
            <div class="help">Credenciales usadas también en la app móvil.</div>
          </div>

          <div class="col-lg-8">
            <form id="userCreateForm" method="POST" action="{{ route('users.store') }}" class="row g-3">
              @csrf

              <div class="col-md-6">
                <label class="form-label">Username *</label>
                <input id="u_username" name="username" class="form-control" value="{{ old('username') }}" placeholder="cobrador01" required>
              </div>

              <div class="col-md-6">
                <label class="form-label">Email *</label>
                <input id="u_email" name="email" type="email" class="form-control" value="{{ old('email') }}" placeholder="cobrador@empresa.com" required>
              </div>

              <div class="col-md-6">
                <label class="form-label">Contraseña *</label>

                <div style="position:relative;">
                  <input
                    id="u_password"
                    name="password"
                    type="password"
                    class="form-control"
                    placeholder="Mínimo 8 caracteres"
                    required
                    style="padding-right:3rem;"
                    autocomplete="new-password"
                  >

                  {{-- UN SOLO OJO --}}
                  <button
                    type="button"
                    id="toggleUserPass"
                    class="btn btn-outline-light"
                    style="position:absolute; right:.5rem; top:50%; transform:translateY(-50%); padding:.35rem .55rem;"
                    aria-label="Mostrar contraseña"
                  >
                    👁️
                  </button>
                </div>

                {{-- Password rules (live) --}}
                <div class="mt-2" style="display:grid; gap:.35rem;">
                  <div class="help" id="rule_len">• 8 caracteres mínimo</div>
                  <div class="help" id="rule_upper">• 1 mayúscula</div>
                  <div class="help" id="rule_lower">• 1 minúscula</div>
                  <div class="help" id="rule_num">• 1 número</div>
                  <div class="help" id="rule_special">• 1 caracter especial (!@#$...)</div>
                </div>
              </div>

              <div class="col-md-6 d-flex align-items-end">
                <button class="btn btn-primary w-100" type="submit">
                  Crear cobrador
                </button>
              </div>
            </form>
          </div>
        </div>

        <hr class="hr-soft">

        {{-- TABLE --}}
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
          <h5 class="m-0" style="font-weight:900;">Lista de usuarios</h5>
          <div class="help">ADMIN no se toca. USER se activa/desactiva o se elimina (si no tiene historial).</div>
        </div>

        <div class="table-wrap">
          <div class="table-responsive">
            <table class="table table-clean table-striped align-middle mb-0" style="min-width:980px;">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Username</th>
                  <th>Email</th>
                  <th>Rol</th>
                  <th>Estado</th>
                  <th class="right">Acciones</th>
                </tr>
              </thead>
              <tbody>
              @forelse($users as $u)
                @php
                  $isAdmin  = (($u['role'] ?? '') === 'ADMIN');
                  $isActive = (bool)($u['is_active'] ?? false);
                  $uid      = $u['id'] ?? null;
                @endphp

                <tr>
                  <td class="mono">{{ $uid }}</td>
                  <td>{{ $u['username'] ?? '—' }}</td>
                  <td>{{ $u['email'] ?? '—' }}</td>
                  <td>
                    <span class="badge-soft {{ $isAdmin ? 'ok' : '' }}">
                      {{ $u['role'] ?? '—' }}
                    </span>
                  </td>
                  <td>
                    <span class="badge-soft {{ $isActive ? 'ok' : 'off' }}">
                      {{ $isActive ? 'Activo' : 'Inactivo' }}
                    </span>
                  </td>

                  <td class="right">
                    @if(!$uid)
                      <span class="muted">Sin ID</span>
                    @elseif($isAdmin)
                      <span class="help">Protegido</span>
                    @else
                      {{-- Toggle --}}
                      <form class="inline" method="POST" action="{{ route('users.toggle', ['userId' => $uid]) }}">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-outline-light btn-sm" type="submit">
                          {{ $isActive ? 'Desactivar' : 'Activar' }}
                        </button>
                      </form>

                      {{-- Delete (modal) --}}
                      <button
                        type="button"
                        class="btn btn-outline-danger btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#deleteUserModal"
                        data-user-id="{{ $uid }}"
                        data-username="{{ $u['username'] ?? '' }}"
                        data-email="{{ $u['email'] ?? '' }}"
                      >
                        Eliminar
                      </button>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center p-4">
                    <div class="help">No hay usuarios para mostrar.</div>
                  </td>
                </tr>
              @endforelse
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>

  </div>
</div>

{{-- MODAL CONFIRM DELETE --}}
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:16px; overflow:hidden;">
      <div class="accent-bar"></div>
      <div class="modal-header">
        <h5 class="modal-title" style="font-weight:900;">Confirmación de eliminación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <div class="help mb-2">Vas a eliminar este cobrador:</div>

        <div class="surface" style="box-shadow:none;">
          <div class="surface-pad">
            <div class="d-flex flex-column gap-1">
              <div><span class="help">ID:</span> <span class="mono" id="m_uid">—</span></div>
              <div><span class="help">Username:</span> <span id="m_user">—</span></div>
              <div><span class="help">Email:</span> <span id="m_email">—</span></div>
            </div>
          </div>
        </div>

        <div class="alert alert-danger mt-3 mb-0">
          Si el cobrador tiene historial (préstamos/pagos), el sistema bloqueará la eliminación y te pedirá desactivarlo.
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-outline-light" type="button" data-bs-dismiss="modal">Cancelar</button>

        <form id="deleteUserForm" method="POST" action="">
          @csrf
          @method('DELETE')
          <button class="btn btn-outline-danger" type="submit">Eliminar definitivamente</button>
        </form>
      </div>
    </div>
  </div>
</div>

{{-- SCRIPTS --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

  // ✅ Limpia formulario si venimos de "creado"
  const shouldClear = @json((bool)session('clear_user_form'));
  if (shouldClear) {
    const f = document.getElementById('userCreateForm');
    if (f) f.reset();
    // también limpia el checklist visual
    setRule('rule_len', false);
    setRule('rule_upper', false);
    setRule('rule_lower', false);
    setRule('rule_num', false);
    setRule('rule_special', false);
  }

  // ✅ Password strength
  const pass = document.getElementById('u_password');
  function setRule(id, ok) {
    const el = document.getElementById(id);
    if (!el) return;
    el.style.color = ok ? '#0a7a63' : '#6b7e96';
    el.style.fontWeight = ok ? '800' : '500';
  }
  function checkPass(p) {
    setRule('rule_len', p.length >= 8);
    setRule('rule_upper', /[A-Z]/.test(p));
    setRule('rule_lower', /[a-z]/.test(p));
    setRule('rule_num', /[0-9]/.test(p));
    setRule('rule_special', /[^A-Za-z0-9]/.test(p));
  }
  if (pass) {
    pass.addEventListener('input', () => checkPass(pass.value || ''));
    checkPass(pass.value || '');
  }

  // ✅ Un solo ojo
  const toggleBtn = document.getElementById('toggleUserPass');
  if (toggleBtn && pass) {
    toggleBtn.addEventListener('click', () => {
      pass.type = (pass.type === 'password') ? 'text' : 'password';
    });
  }

  // ✅ Modal delete: set action + fill data
  const modal = document.getElementById('deleteUserModal');
  if (modal) {
    modal.addEventListener('show.bs.modal', function (event) {
      const btn = event.relatedTarget;
      const uid = btn.getAttribute('data-user-id');
      const un  = btn.getAttribute('data-username') || '—';
      const em  = btn.getAttribute('data-email') || '—';

      document.getElementById('m_uid').textContent = uid;
      document.getElementById('m_user').textContent = un;
      document.getElementById('m_email').textContent = em;

      const form = document.getElementById('deleteUserForm');
      // IMPORTANTE: users.destroy espera userId
      form.action = "{{ url('/users') }}/" + uid;
    });
  }
});
</script>
@endpush
@endsection







