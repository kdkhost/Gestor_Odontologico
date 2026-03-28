(() => {
    const formatCpf = (digits) => digits.replace(/(\d{3})(\d{3})(\d{3})(\d{0,2}).*/, (_, a, b, c, d) => {
        return [a, b, c].filter(Boolean).join('.') + (d ? `-${d}` : '');
    });

    const formatPhone = (digits) => {
        const pattern = digits.length <= 10
            ? /(\d{2})(\d{0,4})(\d{0,4}).*/
            : /(\d{2})(\d{0,5})(\d{0,4}).*/;

        return digits.replace(pattern, (_, ddd, first, second) => {
            const parts = [];

            if (ddd) {
                parts.push(`(${ddd})`);
            }

            if (first) {
                parts.push(first);
            }

            if (second) {
                parts[parts.length - 1] = `${parts.at(-1)}-${second}`;
            }

            return parts.join(' ').trim();
        });
    };

    const bootLoginMask = () => {
        document.querySelectorAll('[data-admin-mask="login"]').forEach((input) => {
            if (input.dataset.maskBooted === '1') {
                return;
            }

            input.dataset.maskBooted = '1';

            input.addEventListener('input', () => {
                const value = input.value.trim();

                if (value.includes('@')) {
                    return;
                }

                const digits = value.replace(/\D+/g, '').slice(0, 11);

                if (!digits) {
                    input.value = '';
                    return;
                }

                if (/[.-]/.test(value)) {
                    input.value = formatCpf(digits);
                    return;
                }

                if (/[()\s]/.test(value) || digits.length <= 10) {
                    input.value = formatPhone(digits);
                    return;
                }

                input.value = digits;
            });
        });
    };

    const installFilamentBridge = (frame) => {
        try {
            const doc = frame.contentDocument;

            if (!doc || doc.getElementById('adminlte-filament-bridge-style')) {
                return;
            }

            const style = doc.createElement('style');
            style.id = 'adminlte-filament-bridge-style';
            style.textContent = `
                .fi-topbar-ctn,
                .fi-sidebar,
                .fi-layout-sidebar-toggle-btn-ctn,
                .fi-sidebar-close-overlay {
                    display: none !important;
                }

                .fi-layout,
                .fi-main-ctn,
                .fi-main {
                    max-width: none !important;
                    width: 100% !important;
                    margin: 0 !important;
                    padding-inline: 0 !important;
                }

                .fi-main {
                    padding: 1rem !important;
                }

                body {
                    background: #f4f6f9 !important;
                }
            `;

            doc.head.appendChild(style);
        } catch (error) {
            console.warn('Nao foi possivel personalizar a tela incorporada do Filament.', error);
        }
    };

    const resizeFrame = (frame) => {
        try {
            const doc = frame.contentDocument;

            if (!doc) {
                return;
            }

            const body = doc.body;
            const html = doc.documentElement;
            const height = Math.max(
                body ? body.scrollHeight : 0,
                html ? html.scrollHeight : 0,
                920,
            );

            frame.style.height = `${height + 12}px`;
        } catch (error) {
            console.warn('Nao foi possivel recalcular a altura do workspace.', error);
        }
    };

    const bootWorkspaceFrames = () => {
        document.querySelectorAll('[data-admin-filament-frame]').forEach((frame) => {
            if (frame.dataset.bridgeBooted === '1') {
                return;
            }

            frame.dataset.bridgeBooted = '1';

            const sync = () => {
                installFilamentBridge(frame);
                resizeFrame(frame);
            };

            frame.addEventListener('load', () => {
                sync();
                window.setTimeout(sync, 250);
                window.setTimeout(sync, 1250);
            });

            window.setInterval(sync, 2000);
        });
    };

    document.addEventListener('DOMContentLoaded', () => {
        bootLoginMask();
        bootWorkspaceFrames();
    });
})();
