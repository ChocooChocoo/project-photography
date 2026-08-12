<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('assignments:check-deadlines')->hourly()->withoutOverlapping()->environments(['production']);
Schedule::command('procurement:escalate-overdue')->hourly()->withoutOverlapping()->environments(['production']);
Schedule::command('bookings:expire-pending')->hourly()->withoutOverlapping()->environments(['production']);
Schedule::command('bookings:send-reminders')->dailyAt('08:00')->withoutOverlapping()->environments(['production']);
Schedule::command('notifications:prune')->daily()->withoutOverlapping()->environments(['production']);
Schedule::command('subscriptions:notify-lifecycle')->daily()->withoutOverlapping()->environments(['production']);
Schedule::command('subscriptions:expire')->hourly()->withoutOverlapping()->environments(['production']);
