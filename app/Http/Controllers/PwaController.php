<?php

namespace App\Http\Controllers;

use App\Models\PwaSubscription;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PwaController extends Controller
{
    public function __construct(private readonly SettingService $settings) {}

    public function manifest(): JsonResponse
    {
        $appName = $this->settings->get('branding', 'app_name', config('app.name'));

        return response()->json([
            'name' => $appName,
            'short_name' => 'Odonto',
            'start_url' => route('portal.dashboard'),
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => '#f4efe6',
            'theme_color' => '#0f766e',
            'description' => 'Portal da clínica odontológica com consultas, documentos e financeiro.',
            'icons' => [
                ['src' => asset('icons/icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => asset('icons/icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png'],
            ],
        ])->header('Content-Type', 'application/manifest+json');
    }

    public function serviceWorker(): Response
    {
        $script = <<<'JS'
const CACHE_NAME = 'odonto-flow-v1';
const OFFLINE_URLS = ['/', '/portal/login'];

self.addEventListener('install', event => {
    event.waitUntil(caches.open(CACHE_NAME).then(cache => cache.addAll(OFFLINE_URLS)));
});

self.addEventListener('fetch', event => {
    if (event.request.method !== 'GET') {
        return;
    }

    event.respondWith(
        caches.match(event.request).then(response => response || fetch(event.request).catch(() => caches.match('/')))
    );
});

self.addEventListener('notificationclick', event => {
    event.notification.close();

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clientList => {
            for (const client of clientList) {
                if ('focus' in client) {
                    return client.focus();
                }
            }

            if (clients.openWindow) {
                return clients.openWindow('/portal');
            }
        })
    );
});
JS;

        return response($script, 200, ['Content-Type' => 'application/javascript']);
    }

    public function storeSubscription(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'url'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
            'contentEncoding' => ['nullable', 'string'],
        ]);

        $subscription = PwaSubscription::query()->updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'user_id' => $request->user()?->id,
                'patient_id' => $request->user()?->patient?->id,
                'public_key' => data_get($data, 'keys.p256dh'),
                'auth_token' => data_get($data, 'keys.auth'),
                'content_encoding' => $data['contentEncoding'] ?? 'aes128gcm',
                'is_active' => true,
                'last_used_at' => now(),
            ],
        );

        return response()->json([
            'message' => 'Assinatura registrada com sucesso.',
            'id' => $subscription->id,
        ]);
    }
}
