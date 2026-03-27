<?php

namespace App\Services;

use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\Http;

class EvolutionWhatsAppService
{
    public function __construct(
        private readonly WhatsAppTemplateService $templates,
        private readonly SettingService $settings,
    ) {}

    public function sendTemplate(NotificationTemplate $template, string $recipientPhone, array $variables = [], array $context = []): array
    {
        $message = $this->templates->render($template, $variables);
        $guard = $this->templates->guard($template, $recipientPhone, $context);

        if (! $guard['allowed']) {
            $this->templates->registerAttempt($template, $recipientPhone, 'blocked', [
                'reason' => $guard['reason'],
                'blocked_until' => $guard['blocked_until'],
            ], $guard['reason'], $message);

            return ['ok' => false, 'status' => 'blocked', 'message' => $guard['reason']];
        }

        $baseUrl = rtrim((string) config('services.evolution.base_url'), '/');
        $instance = (string) config('services.evolution.instance');
        $token = (string) config('services.evolution.token');

        if ($baseUrl === '' || $instance === '' || $token === '') {
            $this->templates->registerAttempt($template, $recipientPhone, 'failed', [
                'reason' => 'Configuração da Evolution API incompleta.',
            ], 'Configuração da Evolution API incompleta.', $message);

            return ['ok' => false, 'status' => 'failed', 'message' => 'Configuração da Evolution API incompleta.'];
        }

        $response = Http::baseUrl($baseUrl)
            ->acceptJson()
            ->withHeaders([
                'apikey' => $token,
            ])
            ->post("/message/sendText/{$instance}", [
                'number' => $this->templates->normalizePhone($recipientPhone),
                'delay' => (int) ($context['delay'] ?? $this->settings->get('whatsapp', 'default_delay_ms', config('clinic.whatsapp.default_delay_ms'))),
                'linkPreview' => (bool) ($context['link_preview'] ?? $this->settings->get('whatsapp', 'link_preview', config('clinic.whatsapp.link_preview'))),
                'text' => $message,
            ]);

        if (! $response->successful()) {
            $this->templates->registerAttempt($template, $recipientPhone, 'failed', [
                'response' => $response->json(),
            ], $response->body(), $message);

            return ['ok' => false, 'status' => 'failed', 'message' => 'A Evolution API rejeitou o envio.'];
        }

        $payload = $response->json();
        $externalId = (string) (data_get($payload, 'key.id') ?? data_get($payload, 'message.key.id') ?? '');

        $this->templates->registerAttempt($template, $recipientPhone, 'sent', [
            'response' => $payload,
        ], null, $message, $externalId ?: null);

        return ['ok' => true, 'status' => 'sent', 'message' => 'Mensagem enviada com sucesso.', 'payload' => $payload];
    }
}
