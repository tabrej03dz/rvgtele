<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');



Schedule::command(
    'followups:send-reminder-notifications'
)
    ->everyMinute()
    ->withoutOverlapping();

Schedule::call(function () {
    Log::info('HOSTINGER AUTOMATIC CRON WORKING', [
        'server_time' => now()->toDateTimeString(),
    ]);
})
    ->name('hostinger-automatic-cron-test')
    ->everyMinute();

Schedule::command(
    'followups:send-reminder-notifications'
)
    ->everyMinute()
    ->withoutOverlapping();
