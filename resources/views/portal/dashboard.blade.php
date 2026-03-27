@extends('layouts.base')

@section('title', 'Portal do paciente · Painel')

@section('content')
    <section class="hero">
        <span class="eyebrow">Portal do paciente</span>
        <h1>{{ $patient?->preferred_name ?: $patient?->name }}</h1>
        <p>Veja suas próximas consultas, documentos pendentes e parcelas em aberto. Instale este portal no celular para uma experiência de aplicativo.</p>
        <div class="nav" style="margin-top: 18px;">
            <form method="post" action="{{ route('portal.logout') }}">
                @csrf
                <button type="submit">Sair</button>
            </form>
            <button type="button" class="primary" id="enable-push">Ativar notificações push</button>
        </div>
    </section>

    <section class="grid grid-3">
        <div class="card">
            <h2>Próximas consultas</h2>
            <div class="list">
                @forelse ($upcomingAppointments as $appointment)
                    <div class="list-item">
                        <strong>{{ optional($appointment->scheduled_start)->format('d/m/Y H:i') }}</strong>
                        <p>{{ $appointment->procedure?->name ?: 'Avaliação odontológica' }}</p>
                        <span class="badge">{{ $appointment->status }}</span>
                    </div>
                @empty
                    <div class="list-item"><p>Nenhuma consulta futura cadastrada.</p></div>
                @endforelse
            </div>
        </div>

        <div class="card">
            <h2>Documentos pendentes</h2>
            <div class="list">
                @forelse ($pendingDocuments as $document)
                    <div class="list-item">
                        <strong>{{ $document->name }}</strong>
                        <p>{{ $document->category }}</p>
                        <form method="post" action="{{ route('portal.documents.accept', $document) }}">
                            @csrf
                            <button type="submit">Aceitar documento</button>
                        </form>
                    </div>
                @empty
                    <div class="list-item"><p>Você não possui documentos pendentes no momento.</p></div>
                @endforelse
            </div>
        </div>

        <div class="card">
            <h2>Parcelas em aberto</h2>
            <div class="list">
                @forelse ($openInstallments as $installment)
                    <div class="list-item">
                        <strong>Parcela {{ $installment->installment_number }}</strong>
                        <p>Vencimento em {{ optional($installment->due_date)->format('d/m/Y') }}</p>
                        <span class="badge">R$ {{ number_format((float) $installment->balance, 2, ',', '.') }}</span>
                    </div>
                @empty
                    <div class="list-item"><p>Sem parcelas pendentes.</p></div>
                @endforelse
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.getElementById('enable-push')?.addEventListener('click', async () => {
            if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('{{ config('services.webpush.public_key') }}')) {
                OdontoFlow.toast('Chaves de push não configuradas ou navegador incompatível.', 'error');
                return;
            }

            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: '{{ config('services.webpush.public_key') }}',
            });

            const response = await fetch('{{ route('portal.pwa.subscribe') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify(subscription),
            });

            if (response.ok) {
                OdontoFlow.toast('Notificações push ativadas com sucesso.');
            }
        });
    </script>
@endpush
