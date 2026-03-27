@extends('layouts.base')

@section('title', 'Portal do paciente · Cadastro')

@section('content')
    <section class="grid grid-2">
        <div class="hero">
            <span class="eyebrow">Cadastro do paciente</span>
            <h1>Crie sua conta para acompanhar o tratamento.</h1>
            <p>Depois do cadastro você poderá consultar agendamentos, aceitar documentos digitais e registrar notificações push no dispositivo instalado.</p>
        </div>

        <div class="card">
            <h2>Criar conta</h2>
            <form method="post" action="{{ route('portal.register.store') }}">
                @csrf
                <label>Nome completo<input type="text" name="name" required></label>
                <div class="grid grid-2">
                    <label>Data de nascimento<input type="date" name="birth_date"></label>
                    <label>CPF<input type="text" name="document" data-mask="cpf" required></label>
                </div>
                <div class="grid grid-2">
                    <label>E-mail<input type="email" name="email"></label>
                    <label>Celular<input type="text" name="phone" data-mask="cellphone" required></label>
                </div>
                <label>WhatsApp<input type="text" name="whatsapp" data-mask="cellphone"></label>
                <input type="hidden" name="whatsapp_opt_in" value="0">
                <label style="display:flex;align-items:flex-start;gap:10px;font-weight:500;">
                    <input type="checkbox" name="whatsapp_opt_in" value="1" style="width:auto;margin-top:4px;">
                    <span>Autorizo receber mensagens operacionais da clínica por WhatsApp, respeitando as regras de envio e a janela oficial de atendimento.</span>
                </label>
                <div class="grid grid-2">
                    <label>Senha<input type="password" name="password" required></label>
                    <label>Confirmar senha<input type="password" name="password_confirmation" required></label>
                </div>
                <button type="submit">Finalizar cadastro</button>
            </form>
        </div>
    </section>
@endsection
