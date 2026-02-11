@extends('layouts.auth')

@section('title','Login - MYBANK')

@section('content')
<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-header">
            <h1>MYBANK</h1>
            <p>Acceso administrador</p>
        </div>

        @if($errors->any())
            <div class="auth-alert">
                {{ $errors->first('login') ?? 'Error de validación' }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}" class="auth-form">
            @csrf

            <label class="auth-label">
                Usuario
                <input name="username" value="{{ old('username') }}" class="auth-input" placeholder="admin" required>
            </label>

            <label class="auth-label">
                Contraseña
                <input name="password" type="password" class="auth-input" placeholder="••••••••" required>
            </label>

            <button class="auth-btn" type="submit">Entrar</button>
        </form>

        <div class="auth-footer">
            <small>MYBANK Web Admin</small>
        </div>
    </div>
</div>
@endsection
