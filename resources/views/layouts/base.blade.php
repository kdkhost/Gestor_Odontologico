<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f766e">
    <title>@yield('title', config('app.name'))</title>
    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
        :root {
            --bg: #f4efe6;
            --bg-card: rgba(255, 255, 255, 0.82);
            --ink: #1f2937;
            --ink-muted: #6b7280;
            --primary: #0f766e;
            --primary-2: #164e63;
            --accent: #f59e0b;
            --danger: #dc2626;
            --line: rgba(15, 118, 110, 0.12);
            --shadow: 0 22px 50px rgba(15, 23, 42, 0.08);
            --radius: 24px;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", ui-sans-serif, system-ui, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top left, rgba(245, 158, 11, 0.22), transparent 30%),
                radial-gradient(circle at right 20%, rgba(22, 78, 99, 0.18), transparent 20%),
                linear-gradient(180deg, #f9f6ef 0%, #eef6f3 100%);
            min-height: 100vh;
        }

        a { color: inherit; text-decoration: none; }
        .shell { width: min(1200px, calc(100% - 32px)); margin: 0 auto; }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 0;
            gap: 16px;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            letter-spacing: 0.02em;
        }
        .brand-badge {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, var(--primary), var(--primary-2));
            color: white;
            box-shadow: var(--shadow);
        }
        .nav {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .nav a, .nav button {
            border: 0;
            border-radius: 999px;
            padding: 12px 18px;
            background: rgba(255, 255, 255, 0.65);
            cursor: pointer;
            color: var(--ink);
            font: inherit;
            box-shadow: inset 0 0 0 1px rgba(15, 118, 110, 0.08);
        }
        .nav .primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-2));
            color: white;
            box-shadow: var(--shadow);
        }
        .hero, .card {
            background: var(--bg-card);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            backdrop-filter: blur(10px);
        }
        .hero {
            padding: 40px;
            margin: 16px 0 24px;
        }
        .card {
            padding: 24px;
        }
        .grid { display: grid; gap: 20px; }
        .grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .eyebrow {
            text-transform: uppercase;
            letter-spacing: 0.18em;
            color: var(--primary);
            font-size: 0.75rem;
            font-weight: 700;
        }
        h1, h2, h3 { margin: 0 0 12px; line-height: 1.1; }
        p { color: var(--ink-muted); line-height: 1.6; }
        form { display: grid; gap: 16px; }
        label { display: grid; gap: 8px; font-size: 0.95rem; font-weight: 600; }
        input, select, textarea {
            width: 100%;
            border-radius: 16px;
            border: 1px solid rgba(15, 118, 110, 0.18);
            background: rgba(255, 255, 255, 0.95);
            padding: 14px 16px;
            font: inherit;
            color: var(--ink);
        }
        textarea { min-height: 120px; resize: vertical; }
        button[type="submit"], .btn {
            border: 0;
            border-radius: 18px;
            padding: 14px 18px;
            background: linear-gradient(135deg, var(--primary), var(--primary-2));
            color: white;
            font-weight: 700;
            cursor: pointer;
            box-shadow: var(--shadow);
        }
        .btn.secondary {
            background: white;
            color: var(--primary-2);
            border: 1px solid rgba(15, 118, 110, 0.2);
            box-shadow: none;
        }
        .muted { color: var(--ink-muted); }
        .stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-top: 22px;
        }
        .stat, .list-item {
            padding: 18px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.72);
            border: 1px solid rgba(15, 118, 110, 0.08);
        }
        .list { display: grid; gap: 14px; }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(15, 118, 110, 0.1);
            color: var(--primary);
            font-size: 0.8rem;
            font-weight: 700;
        }
        .danger { color: var(--danger); }
        .footer {
            padding: 32px 0 48px;
            color: var(--ink-muted);
            font-size: 0.9rem;
        }
        .android-shell .topbar {
            position: sticky;
            top: 0;
            z-index: 10;
            padding-top: max(14px, env(safe-area-inset-top));
            backdrop-filter: blur(10px);
        }
        @media (max-width: 900px) {
            .grid-2, .grid-3, .stats { grid-template-columns: 1fr; }
            .hero { padding: 24px; }
            .shell { width: min(100% - 20px, 1200px); }
            .topbar { align-items: flex-start; flex-direction: column; }
        }
    </style>
    @stack('head')
</head>
<body>
    <div class="shell">
        <header class="topbar">
            <a href="{{ route('home') }}" class="brand">
                <span class="brand-badge">DF</span>
                <span>{{ config('app.name') }}</span>
            </a>
            <nav class="nav">
                <a href="{{ route('home') }}">Início</a>
                <a href="{{ route('portal.login') }}">Portal</a>
                <a class="primary" href="{{ route('filament.admin.auth.login') }}">Administrativo</a>
            </nav>
        </header>

        @yield('content')

        <footer class="footer">
            {{ config('app.name') }} · Laravel 12 · Filament · PWA · Multiunidade
        </footer>
    </div>

    <div id="global-upload-progress" style="position:fixed;left:20px;right:20px;bottom:20px;display:none;z-index:9999;">
        <div class="card" style="padding:14px 18px;">
            <strong>Enviando arquivo...</strong>
            <div class="muted" id="upload-progress-text">0%</div>
            <div style="height:8px;background:#d1fae5;border-radius:999px;margin-top:10px;overflow:hidden;">
                <div id="upload-progress-bar" style="height:100%;width:0;background:linear-gradient(135deg,#0f766e,#164e63);"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.9/dist/inputmask.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const masks = {
            phone: '(99) 9999-9999',
            cellphone: '(99) 99999-9999',
            cpf: '999.999.999-99',
            cnpj: '99.999.999/9999-99',
            cep: '99999-999',
            time: '99:99',
            currency: 'currency',
        };

        document.querySelectorAll('[data-mask]').forEach((element) => {
            const mask = masks[element.dataset.mask];

            if (mask === 'currency') {
                Inputmask('currency', {
                    prefix: 'R$ ',
                    radixPoint: ',',
                    groupSeparator: '.',
                    digits: 2,
                    autoGroup: true,
                    rightAlign: false,
                }).mask(element);
                return;
            }

            if (mask) {
                Inputmask(mask, { clearIncomplete: false }).mask(element);
            }
        });

        document.querySelectorAll('[data-cep-target]').forEach((element) => {
            element.addEventListener('blur', async () => {
                const cep = element.value.replace(/\D+/g, '');
                if (cep.length !== 8) return;
                const response = await fetch(`/viacep/${cep}`);
                if (!response.ok) return;
                const data = await response.json();
                const scope = element.closest('[data-address-scope]') || document;
                const fill = (selector, value) => {
                    const target = scope.querySelector(selector);
                    if (target && !target.value) target.value = value || '';
                };

                fill('[name="street"]', data.logradouro);
                fill('[name="district"]', data.bairro);
                fill('[name="city"]', data.localidade);
                fill('[name="state"]', data.uf);
                fill('[name="complement"]', data.complemento);
            });
        });

        const toast = (message, type = 'success') => {
            Toastify({
                text: message,
                gravity: 'bottom',
                position: 'right',
                duration: 5000,
                style: {
                    background: type === 'error'
                        ? 'linear-gradient(135deg, #dc2626, #7f1d1d)'
                        : 'linear-gradient(135deg, #0f766e, #164e63)',
                },
            }).showToast();
        };

        @if (session('status'))
            toast(@json(session('status')));
        @endif

        @if ($errors->any())
            toast(@json($errors->first()), 'error');
        @endif

        if (window.matchMedia('(display-mode: standalone)').matches) {
            document.body.classList.add('android-shell');
        }

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register(@json(route('pwa.service-worker'))));
        }

        window.OdontoFlow = {
            toast,
            uploadProgress: (progress, label) => {
                const wrapper = document.getElementById('global-upload-progress');
                const bar = document.getElementById('upload-progress-bar');
                const text = document.getElementById('upload-progress-text');
                wrapper.style.display = 'block';
                bar.style.width = `${progress}%`;
                text.textContent = label || `${progress}%`;
                if (progress >= 100) {
                    setTimeout(() => wrapper.style.display = 'none', 900);
                }
            },
        };
    </script>
    @stack('scripts')
</body>
</html>
