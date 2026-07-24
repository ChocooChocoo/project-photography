<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('procurement:escalate-overdue')->hourly();
Schedule::command('bookings:expire-pending')->hourly();
Schedule::command('subscriptions:notify-trial-ending')->daily();
