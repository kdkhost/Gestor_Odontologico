<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\OperationsInsightService;
use App\Services\SettingService;
use App\Services\SystemHealthService;
use App\Support\AdminModuleRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(
        OperationsInsightService $operations,
        SystemHealthService $health,
        SettingService $settings,
    ): View|RedirectResponse {
        $user = auth()->user();

        if (! $user->hasRole('superadmin') && ! $user->can('dashboard.view')) {
            $firstModule = collect(AdminModuleRegistry::visibleFor($user))->first();

            abort_if(blank($firstModule), 403);

            return redirect()->route('admin.workspace', ['slug' => $firstModule['slug']]);
        }

        $snapshot = $operations->snapshot(days: 14, limit: 5);
        $healthSnapshot = $health->snapshot();
        $stats = $snapshot['stats'];

        return view('admin.dashboard', [
            'greeting' => $this->resolveGreeting(),
            'snapshot' => $snapshot,
            'stats' => $stats,
            'appointmentsNeedingConfirmation' => $snapshot['alerts']['appointments_needing_confirmation'],
            'overdueReceivables' => $snapshot['alerts']['overdue_receivables'],
            'lowStockItems' => $snapshot['alerts']['low_stock_items'],
            'health' => $healthSnapshot,
            'healthScore' => collect($healthSnapshot['environment']['requirements']['extensions'])->where('required', true)->where('ok', true)->count(),
            'healthTotal' => collect($healthSnapshot['environment']['requirements']['extensions'])->where('required', true)->count(),
            'moduleGroups' => AdminModuleRegistry::groupedFor($user),
            'systemName' => $settings->get('branding', 'app_name', config('app.name')),
            'systemVersion' => $settings->get('branding', 'system_version', config('clinic.system_version')),
        ]);
    }

    private function resolveGreeting(): string
    {
        $hour = now(config('app.timezone'))->hour;

        return match (true) {
            $hour < 12 => 'Bom dia',
            $hour < 18 => 'Boa tarde',
            default => 'Boa noite',
        };
    }
}
