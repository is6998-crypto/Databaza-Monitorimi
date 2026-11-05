<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled tasks (run by the "scheduler" container: php artisan schedule:work)
|--------------------------------------------------------------------------
| Times are UTC (Kosovo is UTC+1, UTC+2 in summer).
*/

// Daily encrypted backup of the database and private files, kept on the off-site disk.
Schedule::command('backup:clean')->dailyAt('01:30')->onOneServer();
Schedule::command('backup:run')->dailyAt('02:00')->onOneServer()->withoutOverlapping();
Schedule::command('backup:monitor')->dailyAt('07:00')->onOneServer();

// Queue metrics for Horizon.
Schedule::command('horizon:snapshot')->everyFiveMinutes();

// Housekeeping.
Schedule::command('sanctum:prune-expired --hours=24')->daily();
Schedule::command('auth:clear-resets')->everyFifteenMinutes();
