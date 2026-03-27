<?php

namespace App\Services;

use App\Models\NotificationTemplate;
use App\Models\WhatsAppDispatchLog;
use Illuminate\Support\Carbon;

class WhatsAppTemplateService
{
    public function __construct(private readonly SettingService $settings) {}

    public function render(NotificationTemplate $template, array $variables = []): string
    {
        $message = $template->message ?? '';

        foreach ($variables as $key => $value) {
            $message = str_replace('{{'.$key.'}}', (string) $value, $message);
        }

        $message = preg_replace('/{{[^}]+}}/', '', $message) ?? $message;
        $message = $this->htmlToText($message);

        $signature = trim((string) $this->settings->get('whatsapp', 'signature', config('clinic.whatsapp.signature')));

        if ($signature !== '') {
            $message = trim($message)."\n\n".$this->replaceSignatureVariables($signature, $variables);
        }

        $message = preg_replace("/\n{3,}/", "\n\n", $message) ?? $message;

        return trim($message);
    }

    public function guard(NotificationTemplate $template, string $recipientPhone, array $context = []): array
    {
        $phone = $this->normalizePhone($recipientPhone);
        $now = $context['now'] ?? now(config('app.timezone'));

        if ($phone === '') {
            return ['allowed' => false, 'reason' => 'Destinatário inválido.', 'blocked_until' => null];
        }

        if (! $template->is_active || $template->channel !== 'whatsapp') {
            return ['allowed' => false, 'reason' => 'Template inativo ou incompatível com WhatsApp.', 'blocked_until' => null];
        }

        if (! $this->settings->get('whatsapp', 'dispatch_enabled', config('clinic.whatsapp.dispatch_enabled'))) {
            return ['allowed' => false, 'reason' => 'Envio automatizado de WhatsApp está desativado.', 'blocked_until' => null];
        }

        if (($template->requires_opt_in ?? true) && ! ($context['opt_in_confirmed'] ?? false) && $this->settings->get('whatsapp', 'require_opt_in', config('clinic.whatsapp.require_opt_in'))) {
            return ['allowed' => false, 'reason' => 'Opt-in do destinatário não foi informado.', 'blocked_until' => null];
        }

        $respectBusinessHours = $this->settings->get('whatsapp', 'respect_business_hours', config('clinic.whatsapp.respect_business_hours'))
            && ($template->requires_official_window ?? true);

        if ($respectBusinessHours) {
            $start = $template->delivery_window_start ?: $this->settings->get('whatsapp', 'business_hours_start', config('clinic.whatsapp.business_hours_start'));
            $end = $template->delivery_window_end ?: $this->settings->get('whatsapp', 'business_hours_end', config('clinic.whatsapp.business_hours_end'));
            $current = $now->format('H:i');

            if ($current < substr((string) $start, 0, 5) || $current > substr((string) $end, 0, 5)) {
                return [
                    'allowed' => false,
                    'reason' => 'Fora da janela permitida para envio.',
                    'blocked_until' => Carbon::parse($now->toDateString().' '.substr((string) $start, 0, 5), config('app.timezone'))->addDay(),
                ];
            }
        }

        $cooldown = (int) ($template->cooldown_seconds ?: $this->settings->get('whatsapp', 'min_interval_seconds', config('clinic.whatsapp.min_interval_seconds')));
        $lastSent = WhatsAppDispatchLog::query()
            ->where('recipient_phone', $phone)
            ->where('status', 'sent')
            ->latest('sent_at')
            ->first();

        if ($lastSent?->sent_at && $lastSent->sent_at->diffInSeconds($now) < $cooldown) {
            return [
                'allowed' => false,
                'reason' => 'Intervalo mínimo entre mensagens ainda não atingido.',
                'blocked_until' => $lastSent->sent_at->copy()->addSeconds($cooldown),
            ];
        }

        $perMinuteLimit = (int) $this->settings->get('whatsapp', 'max_per_minute', config('clinic.whatsapp.max_per_minute'));
        $minuteCount = WhatsAppDispatchLog::query()
            ->where('status', 'sent')
            ->where('attempted_at', '>=', $now->copy()->subMinute())
            ->count();

        if ($minuteCount >= $perMinuteLimit) {
            return [
                'allowed' => false,
                'reason' => 'Limite global por minuto atingido.',
                'blocked_until' => $now->copy()->addMinute(),
            ];
        }

        $hourlyLimit = (int) ($template->hourly_limit_per_recipient ?: $this->settings->get('whatsapp', 'max_per_hour_per_recipient', config('clinic.whatsapp.max_per_hour_per_recipient')));
        $hourCount = WhatsAppDispatchLog::query()
            ->where('recipient_phone', $phone)
            ->where('status', 'sent')
            ->where('attempted_at', '>=', $now->copy()->subHour())
            ->count();

        if ($hourCount >= $hourlyLimit) {
            return [
                'allowed' => false,
                'reason' => 'Limite por destinatário na última hora atingido.',
                'blocked_until' => $now->copy()->addHour(),
            ];
        }

        return ['allowed' => true, 'reason' => null, 'blocked_until' => null];
    }

    public function registerAttempt(NotificationTemplate $template, string $recipientPhone, string $status, array $meta = [], ?string $errorMessage = null, ?string $message = null, ?string $externalId = null): WhatsAppDispatchLog
    {
        $attemptedAt = now(config('app.timezone'));

        return WhatsAppDispatchLog::query()->create([
            'notification_template_id' => $template->id,
            'unit_id' => $template->unit_id,
            'recipient_phone' => $this->normalizePhone($recipientPhone),
            'trigger_event' => $template->trigger_event,
            'provider' => $template->provider ?: 'evolution',
            'status' => $status,
            'external_id' => $externalId,
            'message_hash' => $message ? hash('sha256', $message) : null,
            'attempted_at' => $attemptedAt,
            'sent_at' => $status === 'sent' ? $attemptedAt : null,
            'blocked_until' => $meta['blocked_until'] ?? null,
            'error_message' => $errorMessage,
            'meta' => $meta,
        ]);
    }

    private function htmlToText(string $value): string
    {
        $value = str_ireplace(['<br>', '<br/>', '<br />', '</p>', '</div>', '</li>'], "\n", $value);
        $value = preg_replace('/<li>/i', '- ', $value) ?? $value;
        $value = strip_tags($value);
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $lines = collect(explode("\n", str_replace("\r", '', $value)))
            ->map(fn (string $line) => trim($line))
            ->all();

        return trim(implode("\n", $lines));
    }

    private function replaceSignatureVariables(string $signature, array $variables): string
    {
        $signature = str_replace('{{app_name}}', (string) $this->settings->get('branding', 'app_name', config('app.name')), $signature);

        foreach ($variables as $key => $value) {
            $signature = str_replace('{{'.$key.'}}', (string) $value, $signature);
        }

        return $signature;
    }

    public function normalizePhone(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if ($digits === '') {
            return '';
        }

        $digits = ltrim($digits, '0');

        if (str_starts_with($digits, '55') && strlen($digits) >= 12) {
            return $digits;
        }

        if (in_array(strlen($digits), [10, 11], true)) {
            return '55'.$digits;
        }

        return $digits;
    }
}
