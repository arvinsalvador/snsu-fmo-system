<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('reports:process-due')->everyMinute()->withoutOverlapping();
Schedule::command('kpis:evaluate-due')->dailyAt('01:00')->withoutOverlapping();
Schedule::command('kpis:notify-corrective-actions')->dailyAt('08:00')->withoutOverlapping();
