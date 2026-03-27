@extends('layouts.base')

@section('title', 'Portal do paciente · Login')

@section('content')
    <section class="grid grid-2">
        <div class="hero">
            <span class="eyebrow">Portal do paciente</span>
            <h1>Entre com e-mail, CPF ou celular.</h1>
            <p>Ao acessar o portal você acompanha consultas, documentos digitais pendentes e parcelas em aberto em uma experiência pronta para celular e PWA.</p>
        </div>

        <div class="card">
            <h2>Acessar portal</h2>
            <form method="post" action="{{ route('portal.login.attempt') }}">
                @csrf
                <label>Identificador<input type="text" name="identifier" placeholder="E-mail, CPF ou celular" required></label>
                <label>Senha<input type="password" name="password" required></label>
                <button type="submit">Entrar</button>
                <a class="btn secondary" href="{{ route('portal.register') }}">Criar conta</a>
            </form>
        </div>
    </section>
@endsection
