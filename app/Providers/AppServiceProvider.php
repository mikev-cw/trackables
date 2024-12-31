<?php

namespace App\Providers;

use App\Models\Trackable;
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
        // Bind in routes the 'uid' param instead of the default
        $this->app['router']->bind('trackable', function ($value) {
            return Trackable::where('uid', $value)->firstOrFail();
        });
    }
}
