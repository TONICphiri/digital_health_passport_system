<?php

use Illuminate\Support\Facades\Schedule;

/*
| Scheduled tasks. On cPanel, add one cron job that runs every minute:
| php /home/USERNAME/health-passport/artisan schedule:run >> /dev/null 2>&1
*/

Schedule::command('reminders:send')->dailyAt('07:00')->withoutOverlapping();
Schedule::command('backup:run --scheduled')->dailyAt(config('health_passport.backup.time'))->withoutOverlapping();
Schedule::command('patients:separate-adults')->dailyAt('01:00')->withoutOverlapping();
