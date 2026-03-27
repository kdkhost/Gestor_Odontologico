<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid gap-4 md:grid-cols-3">
            <label class="space-y-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Unidade</span>
                <select id="calendar-unit" class="fi-input block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    <option value="">Todas</option>
                    @foreach (\App\Models\Unit::query()->orderBy('name')->get() as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                    @endforeach
                </select>
            </label>

            <label class="space-y-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Profissional</span>
                <select id="calendar-professional" class="fi-input block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    <option value="">Todos</option>
                    @foreach (\App\Models\Professional::query()->with('user')->orderBy('id')->get() as $professional)
                        <option value="{{ $professional->id }}">{{ $professional->user?->name ?? 'Sem usuário' }}</option>
                    @endforeach
                </select>
            </label>

            <label class="space-y-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Cadeira / sala</span>
                <select id="calendar-chair" class="fi-input block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    <option value="">Todas</option>
                    @foreach (\App\Models\Chair::query()->orderBy('name')->get() as $chair)
                        <option value="{{ $chair->id }}">{{ $chair->name }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <div id="fullcalendar"></div>
        </div>
    </div>

    @push('head')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@4.4.2/core/main.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@4.4.2/daygrid/main.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@4.4.2/timegrid/main.min.css">
        <style>
            #fullcalendar .fc-toolbar h2 { font-size: 1.1rem; font-weight: 700; }
            #fullcalendar .fc-event { border-radius: 10px; padding: 2px 4px; }
            #fullcalendar .fc-button {
                background: #0f766e;
                border-color: #0f766e;
                box-shadow: none;
            }
            #fullcalendar .fc-button-primary:not(:disabled).fc-button-active,
            #fullcalendar .fc-button-primary:not(:disabled):active {
                background: #164e63;
                border-color: #164e63;
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/moment@2.30.1/min/moment.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@4.4.2/core/main.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@4.4.2/core/locales/pt-br.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@4.4.2/interaction/main.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@4.4.2/daygrid/main.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@4.4.2/timegrid/main.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const calendarElement = document.getElementById('fullcalendar');
                const unit = document.getElementById('calendar-unit');
                const professional = document.getElementById('calendar-professional');
                const chair = document.getElementById('calendar-chair');

                if (!calendarElement) {
                    return;
                }

                const calendar = new FullCalendar.Calendar(calendarElement, {
                    plugins: ['dayGrid', 'timeGrid', 'interaction'],
                    locale: 'pt-br',
                    initialView: 'timeGridWeek',
                    nowIndicator: true,
                    height: 'auto',
                    header: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay',
                    },
                    buttonText: {
                        today: 'Hoje',
                        month: 'Mês',
                        week: 'Semana',
                        day: 'Dia',
                    },
                    eventTimeFormat: {
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: false,
                    },
                    events(fetchInfo, successCallback, failureCallback) {
                        const params = new URLSearchParams({
                            start: fetchInfo.startStr,
                            end: fetchInfo.endStr,
                            unit_id: unit.value,
                            professional_id: professional.value,
                            chair_id: chair.value,
                        });

                        fetch(@json(route('admin.calendar.feed')) + '?' + params.toString(), {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        })
                            .then(response => {
                                if (! response.ok) {
                                    throw new Error('Falha ao carregar agenda.');
                                }

                                return response.json();
                            })
                            .then(successCallback)
                            .catch(failureCallback);
                    },
                    eventClick(info) {
                        const props = info.event.extendedProps;

                        if (! window.Swal) {
                            return;
                        }

                        Swal.fire({
                            title: info.event.title,
                            html: `
                                <div style="text-align:left">
                                    <p><strong>Status:</strong> ${props.status ?? '-'}</p>
                                    <p><strong>Unidade:</strong> ${props.unit ?? '-'}</p>
                                    <p><strong>Profissional:</strong> ${props.professional ?? '-'}</p>
                                    <p><strong>Cadeira:</strong> ${props.chair ?? '-'}</p>
                                    <p><strong>Observações:</strong> ${props.notes ?? 'Sem observações.'}</p>
                                </div>
                            `,
                            confirmButtonText: 'Fechar',
                        });
                    },
                });

                calendar.render();

                [unit, professional, chair].forEach((field) => {
                    field.addEventListener('change', () => calendar.refetchEvents());
                });
            });
        </script>
    @endpush
</x-filament-panels::page>
