<?php

namespace App\Http\Controllers;

use App\Models\Procedure;
use App\Models\Unit;
use App\Services\GreetingService;
use App\Services\SettingService;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function __construct(
        private readonly GreetingService $greetings,
        private readonly SettingService $settings,
    ) {}

    public function index(): View
    {
        return view('public.home', [
            'greeting' => $this->greetings->current(),
            'appName' => $this->settings->get('branding', 'app_name', config('app.name')),
            'units' => Unit::query()->where('is_active', true)->orderBy('name')->get(),
            'procedures' => Procedure::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
