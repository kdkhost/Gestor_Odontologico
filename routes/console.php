<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('clinic:automation-run')
    ->everyFifteenMinutes()
    ->withoutOverlapping();

Schedule::command('clinic:nfse-submit --limit=30')
    ->everyFifteenMinutes()
    ->withoutOverlapping();

Schedule::command('clinic:insurance-authorizations-expire')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('clinic:insurance-claims-create-drafts')
    ->dailyAt('01:30')
    ->withoutOverlapping();
