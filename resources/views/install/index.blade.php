@extends('layouts.base')

@section('title', 'Instalador · '.config('app.name'))

@section('content')
    <section class="hero">
        <span class="eyebrow">Instalador automático</span>
        <h1>Preparar a clínica odontológica para o primeiro acesso.</h1>
        <p>O instalador valida o ambiente, testa o banco informado, grava o `.env`, executa migrações, aplica seed inicial e cria o primeiro superadmin.</p>

        <div class="stats">
            <div class="stat">
                <strong>Status geral</strong>
                <p class="{{ $requirements['ready'] ? '' : 'danger' }}">{{ $requirements['ready'] ? 'Ambiente pronto para instalar.' : 'Corrija os itens críticos antes de continuar.' }}</p>
            </div>
            <div class="stat">
                <strong>PHP atual</strong>
                <p>{{ $requirements['php']['current'] }} · mínimo {{ $requirements['php']['required'] }}</p>
            </div>
            <div class="stat">
                <strong>Hospedagem alvo</strong>
                <p>cPanel / CloudLinux, com suporte a Laravel, filas em banco e scheduler por cron.</p>
            </div>
        </div>
    </section>

    <section class="grid grid-2">
        <div class="card">
            <h2>Checklist do ambiente</h2>
            <div class="list">
                <div class="list-item">
                    <strong>PHP {{ $requirements['php']['required'] }}</strong>
                    <p class="{{ $requirements['php']['ok'] ? '' : 'danger' }}">{{ $requirements['php']['ok'] ? 'Compatível' : 'Versão incompatível' }}</p>
                </div>

                @foreach ($requirements['extensions'] as $extension)
                    <div class="list-item">
                        <strong>Extensão {{ $extension['label'] }}</strong>
                        <p class="{{ $extension['ok'] ? '' : 'danger' }}">{{ $extension['ok'] ? 'Disponível' : 'Indisponível' }}</p>
                    </div>
                @endforeach

                @foreach ($requirements['paths'] as $path)
                    <div class="list-item">
                        <strong>{{ $path['label'] }}</strong>
                        <p class="{{ $path['ok'] ? '' : 'danger' }}">{{ $path['ok'] ? 'Gravável' : 'Sem permissão de escrita' }}</p>
                        <p>{{ $path['path'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card">
            <h2>Configuração inicial</h2>
            <form method="post" action="{{ route('install.store') }}">
                @csrf

                <div class="grid grid-2">
                    <label>Nome do sistema<input type="text" name="app_name" value="{{ old('app_name', 'Odonto Flow') }}" required></label>
                    <label>URL base<input type="url" name="app_url" value="{{ old('app_url', request()->getSchemeAndHttpHost()) }}" required></label>
                </div>

                <label>Nome da unidade principal<input type="text" name="unit_name" value="{{ old('unit_name', 'Clínica Matriz') }}" required></label>

                <div class="grid grid-2">
                    <label>Banco de dados
                        <select name="db_connection" required>
                            <option value="mariadb" @selected(old('db_connection', 'mariadb') === 'mariadb')>MariaDB</option>
                            <option value="mysql" @selected(old('db_connection') === 'mysql')>MySQL</option>
                            <option value="sqlite" @selected(old('db_connection') === 'sqlite')>SQLite local</option>
                        </select>
                    </label>
                    <label>Host<input type="text" name="db_host" value="{{ old('db_host', 'localhost') }}"></label>
                </div>

                <div class="grid grid-3">
                    <label>Porta<input type="text" name="db_port" value="{{ old('db_port', '3306') }}"></label>
                    <label>Database<input type="text" name="db_database" value="{{ old('db_database') }}"></label>
                    <label>Usuário<input type="text" name="db_username" value="{{ old('db_username') }}"></label>
                </div>

                <label>Senha do banco<input type="password" name="db_password"></label>

                <div class="grid grid-2">
                    <label>Nome do superadmin<input type="text" name="admin_name" value="{{ old('admin_name', 'Administrador') }}" required></label>
                    <label>E-mail do superadmin<input type="email" name="admin_email" value="{{ old('admin_email') }}" required></label>
                </div>

                <div class="grid grid-2">
                    <label>Telefone<input type="text" name="admin_phone" data-mask="cellphone" value="{{ old('admin_phone') }}"></label>
                    <label>Documento<input type="text" name="admin_document" data-mask="cpf" value="{{ old('admin_document') }}"></label>
                </div>

                <div class="grid grid-2">
                    <label>Senha<input type="password" name="admin_password" required></label>
                    <label>Confirmar senha<input type="password" name="admin_password_confirmation" required></label>
                </div>

                <p class="muted">A conexão com o banco é testada antes da instalação definitiva.</p>

                <button type="submit" @disabled(! $requirements['ready'])>Instalar sistema</button>
            </form>
        </div>
    </section>
@endsection
