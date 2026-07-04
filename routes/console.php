<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('wa:dispatch')->everyMinute();
Schedule::command('wa:monitor-senders')->everyMinute();
Schedule::command('wa:reset-daily-counters')->dailyAt('00:00');
