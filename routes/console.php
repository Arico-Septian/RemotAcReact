<?php

use App\Models\AppSetting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('logs:clean')
    ->dailyAt('07:00');

Schedule::command('device:check-status')
    ->everyMinute()
    ->when(fn (): bool => ((int) now()->format('i')) % AppSetting::deviceCheckIntervalMinutes() === 0)
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('ac:run-timer')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('fuzzy:run')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('notification:cleanup')
    ->dailyAt('00:00');

Schedule::command('temperature:cleanup')
    ->dailyAt('00:10');

Schedule::call(function () {
    DB::table('cache')->where('expiration', '<', time())->delete();
    DB::table('cache_locks')->where('expiration', '<', time())->delete();
})->dailyAt('00:20');
