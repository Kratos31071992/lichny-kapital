<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Расписание задач (Scheduler)
|--------------------------------------------------------------------------
| На сервере достаточно одной cron-записи:
|   * * * * * cd /var/www && php artisan schedule:run >> /dev/null 2>&1
| Все задачи ниже выполняются через неё.
*/

// Ежедневный бэкап базы данных в 03:00 (ТЗ: автоматическое копирование).
Schedule::command('backup:database')->dailyAt('03:00')->withoutOverlapping();
