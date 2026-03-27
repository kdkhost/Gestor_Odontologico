@extends('layouts.base')

@section('title', $appName.' · Clínica Odontológica')

@section('content')
    <section class="hero">
        <span class="eyebrow">{{ $greeting }}</span>
        <h1>Gestão odontológica com portal do paciente, financeiro e agenda integrada.</h1>
        <p>Solicite uma consulta, acompanhe documentos e tenha acesso ao portal da clínica em uma experiência pronta para PWA.</p>

        <div class="stats">
            <div class="stat">
                <strong>Multiunidade</strong>
                <p>Agendas, estoque e financeiro organizados por unidade.</p>
            </div>
            <div class="stat">
                <strong>Portal do paciente</strong>
                <p>Documentos, parcelas e histórico de consultas em um só lugar.</p>
            </div>
            <div class="stat">
                <strong>Fluxo operacional</strong>
                <p>Solicitação online, confirmação pela recepção e comunicação por WhatsApp.</p>
            </div>
        </div>
    </section>

    <section class="grid grid-2">
        <div class="card">
            <span class="eyebrow">Solicitação Pública</span>
            <h2>Solicitar agendamento</h2>
            <p>Escolha a unidade, informe seus dados e a recepção fará a confirmação do melhor horário.</p>

            <form method="post" action="{{ route('appointments.request') }}" data-address-scope>
                @csrf
                <div class="grid grid-2">
                    <label>Unidade
                        <select name="unit_id" required>
                            <option value="">Selecione</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Procedimento desejado
                        <select name="procedure_id">
                            <option value="">Primeira avaliação</option>
                            @foreach ($procedures as $procedure)
                                <option value="{{ $procedure->id }}">{{ $procedure->name }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <div class="grid grid-2">
                    <label>Nome completo<input type="text" name="name" required></label>
                    <label>Nome preferido<input type="text" name="preferred_name"></label>
                </div>
                <div class="grid grid-3">
                    <label>Data de nascimento<input type="date" name="birth_date"></label>
                    <label>Celular<input type="text" name="phone" data-mask="cellphone" required></label>
                    <label>WhatsApp<input type="text" name="whatsapp" data-mask="cellphone"></label>
                </div>
                <div class="grid grid-2">
                    <label>E-mail<input type="email" name="email"></label>
                    <label>CPF<input type="text" name="cpf" data-mask="cpf"></label>
                </div>
                <div class="grid grid-3">
                    <label>Data desejada<input type="date" name="requested_date" required></label>
                    <label>Hora desejada<input type="text" name="requested_time" data-mask="time" placeholder="09:30" required></label>
                    <label>CEP<input type="text" name="zip_code" data-mask="cep" data-cep-target="true"></label>
                </div>
                <div class="grid grid-2">
                    <label>Rua<input type="text" name="street"></label>
                    <label>Número<input type="text" name="number"></label>
                </div>
                <div class="grid grid-3">
                    <label>Complemento<input type="text" name="complement"></label>
                    <label>Bairro<input type="text" name="district"></label>
                    <label>Cidade / UF
                        <div class="grid grid-2">
                            <input type="text" name="city" placeholder="Cidade">
                            <input type="text" name="state" maxlength="2" placeholder="UF">
                        </div>
                    </label>
                </div>
                <label>Observações
                    <textarea name="notes" placeholder="Dor, urgência, melhor período do dia e observações importantes."></textarea>
                </label>
                <input type="hidden" name="whatsapp_opt_in" value="0">
                <label style="display:flex;align-items:flex-start;gap:10px;font-weight:500;">
                    <input type="checkbox" name="whatsapp_opt_in" value="1" style="width:auto;margin-top:4px;">
                    <span>Autorizo o envio de mensagens operacionais da clínica por WhatsApp para confirmação de consulta, lembretes e cobranças dentro da janela oficial de atendimento.</span>
                </label>
                <button type="submit">Enviar solicitação</button>
            </form>
        </div>

        <div class="grid">
            <div class="card">
                <span class="eyebrow">Portal do Paciente</span>
                <h2>Acompanhe consultas, termos e cobranças.</h2>
                <p>Faça login para visualizar consultas futuras, aceitar documentos digitais e registrar o PWA no seu dispositivo.</p>
                <div class="nav" style="padding-top: 8px;">
                    <a class="primary" href="{{ route('portal.login') }}">Entrar no portal</a>
                    <a href="{{ route('portal.register') }}">Criar conta</a>
                </div>
            </div>

            <div class="card">
                <span class="eyebrow">Unidades Ativas</span>
                <div class="list">
                    @forelse ($units as $unit)
                        <div class="list-item">
                            <strong>{{ $unit->name }}</strong>
                            <p>{{ trim(collect([$unit->street, $unit->number, $unit->district, $unit->city, $unit->state])->filter()->join(', '), ', ') ?: 'Endereço configurado no administrativo.' }}</p>
                            <span class="badge">{{ $unit->phone ?: 'Telefone no cadastro interno' }}</span>
                        </div>
                    @empty
                        <div class="list-item">
                            <strong>Nenhuma unidade ativa.</strong>
                            <p>Configure a primeira unidade no administrativo após a instalação.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
@endsection
