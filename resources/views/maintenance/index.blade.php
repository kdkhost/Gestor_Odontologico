@extends('layouts.base')

@section('title', 'Manutenção programada')

@section('content')
    <section class="hero">
        <span class="eyebrow">{{ $greeting }}</span>
        <h1>{{ $appName }} está em manutenção programada.</h1>
        <p>Estamos finalizando ajustes operacionais e liberaremos o acesso automaticamente ao fim da janela programada.</p>
        <div class="stats">
            <div class="stat">
                <strong>Liberação prevista</strong>
                <p>{{ $releaseAt ? \Illuminate\Support\Carbon::parse($releaseAt)->format('d/m/Y H:i') : 'Em breve' }}</p>
            </div>
            <div class="stat">
                <strong>Status</strong>
                <p>Ambiente protegido por IP, dispositivo e usuários autorizados.</p>
            </div>
            <div class="stat">
                <strong>Contagem regressiva</strong>
                <p id="maintenance-counter">Calculando...</p>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        const counter = document.getElementById('maintenance-counter');
        const releaseAt = @json($releaseAt);

        if (counter && releaseAt) {
            const tick = () => {
                const diff = new Date(releaseAt).getTime() - Date.now();

                if (diff <= 0) {
                    counter.textContent = 'Liberando agora';
                    location.reload();
                    return;
                }

                const hours = Math.floor(diff / 36e5);
                const minutes = Math.floor((diff % 36e5) / 6e4);
                const seconds = Math.floor((diff % 6e4) / 1e3);
                counter.textContent = `${hours}h ${minutes}m ${seconds}s`;
            };

            tick();
            setInterval(tick, 1000);
        }
    </script>
@endpush
