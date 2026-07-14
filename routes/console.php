<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('documents:check-expiry')->dailyAt('08:00')->withoutOverlapping();
Schedule::command('assets:check-certificates')->dailyAt('06:00')->withoutOverlapping();
Schedule::command('assets:check-inspections')->dailyAt('06:30')->withoutOverlapping();
