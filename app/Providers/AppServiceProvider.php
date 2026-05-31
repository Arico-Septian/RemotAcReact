<?php

namespace App\Providers;

use App\Services\MqttService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MqttService::class);
    }

    public function boot(): void
    {
        Paginator::useTailwind();
    }
}
