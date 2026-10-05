<?php

namespace App\Providers;

use App\Listeners\RecordReminderDelivery;
use App\Services\SettingService;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingService::class);
    }

    public function boot(): void
    {
        Paginator::defaultView('components.pagination');

        Event::listen(NotificationSent::class, [RecordReminderDelivery::class, 'onSent']);
        Event::listen(NotificationFailed::class, [RecordReminderDelivery::class, 'onFailed']);

        // Five sign in attempts per minute for each email address and device.
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by(strtolower((string) $request->input('email')).'|'.$request->ip()));

        // Opening passports is limited so a stolen account cannot be used to
        // try many passport numbers.
        RateLimiter::for('passport-open', fn (Request $request) => Limit::perMinute(20)->by((string) $request->user()?->id));

        // The system name is edited on the Settings page, so every page reads
        // it from the settings table instead of a fixed value.
        // If the database cannot be reached, for example on an error page, the
        // application name from the environment file is used instead.
        View::composer('*', function ($view) {
            try {
                $settings = app(SettingService::class);
                $name = $settings->systemName();
                $country = $settings->countryName();
                $authority = $settings->issuingAuthority();
            } catch (Throwable) {
                $name = config('app.name');
                $country = $authority = '';
            }

            $view->with(['systemName' => $name, 'countryName' => $country, 'issuingAuthority' => $authority]);
        });
    }
}
