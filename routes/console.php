<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
|
| On the 1st of every month at 00:05 the system automatically generates
| performance statistics for every doctor for the previous calendar month.
|
| For this to run on XAMPP (Windows), add a Windows Task Scheduler entry
| that executes the following command every minute:
|
|   php C:\xampp\htdocs\Cardiovascular_system\Cardiovascular_system\artisan schedule:run
|
| On Linux/cPanel add to crontab:
|   * * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
|
*/
Schedule::command('reports:generate-monthly')
    ->monthlyOn(1, '00:05')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/monthly-reports.log'));
