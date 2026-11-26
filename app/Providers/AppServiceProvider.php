<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Антиспам (п. 2 ТЗ): лимитеры на чувствительные формы.
        // Регистрация — 5 попыток в минуту с одного IP.
        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        // Вход и восстановление пароля — 5 попыток в минуту (защита от перебора).
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
    }
}
