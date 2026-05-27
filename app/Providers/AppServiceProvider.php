<?php

namespace App\Providers;

use App\Services\MqttService;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Session;
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

        Event::listen(Login::class, function (Login $event) {
            $currentId = Session::getId() ?? '';
            DB::table('sessions')
                ->where('user_id', $event->user->getAuthIdentifier())
                ->where('id', '!=', $currentId)
                ->delete();
        });
    }
}
