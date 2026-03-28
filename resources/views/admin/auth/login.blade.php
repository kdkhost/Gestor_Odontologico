@extends('adminlte::auth.auth-page', ['authType' => 'login'])

@section('title', 'Acesso administrativo')

@section('adminlte_css')
    @parent
    <link rel="stylesheet" href="{{ asset('css/admin-panel.css') }}">
@stop

@section('auth_header')
    <div class="admin-auth-brand">
        <span class="admin-auth-badge">AdminLTE 3.2</span>
        <h1>Odonto Flow</h1>
        <p>Entre com e-mail, CPF ou celular para acessar o painel administrativo.</p>
    </div>
@stop

@section('auth_body')
    @if (session('status'))
        <div class="alert alert-success small">
            {{ session('status') }}
        </div>
    @endif

    <form action="{{ route('admin.login.attempt') }}" method="post" autocomplete="on">
        @csrf

        <div class="input-group mb-3">
            <input
                type="text"
                name="login"
                value="{{ old('login') }}"
                class="form-control @error('login') is-invalid @enderror"
                placeholder="E-mail, CPF ou celular"
                data-admin-mask="login"
                autofocus
            >
            <div class="input-group-append">
                <div class="input-group-text">
                    <span class="fas fa-user-shield"></span>
                </div>
            </div>
            @error('login')
                <span class="invalid-feedback d-block" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <div class="input-group mb-3">
            <input
                type="password"
                name="password"
                class="form-control @error('password') is-invalid @enderror"
                placeholder="Senha"
            >
            <div class="input-group-append">
                <div class="input-group-text">
                    <span class="fas fa-lock"></span>
                </div>
            </div>
            @error('password')
                <span class="invalid-feedback d-block" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="icheck-primary">
                <input type="checkbox" name="remember" id="remember" value="1" @checked(old('remember'))>
                <label for="remember">Manter conectado</label>
            </div>
            <span class="text-muted small">Acesso protegido por perfil e horario</span>
        </div>

        <button type="submit" class="btn btn-primary btn-block">
            <i class="fas fa-sign-in-alt mr-2"></i>Entrar no painel
        </button>
    </form>
@stop

@section('auth_footer')
    <div class="admin-auth-footer text-center">
        <p class="mb-1">Agenda, financeiro, convenios, estoque e governanca em um unico painel.</p>
        <a href="{{ url('/') }}" class="small">Voltar para o site inicial</a>
    </div>
@stop
