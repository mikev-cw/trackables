<?php

namespace App\Providers;

use App\Models\Trackable;
use Illuminate\Pagination\Paginator;
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
        Paginator::defaultView('vendor.pagination.tabler');
        Paginator::defaultSimpleView('vendor.pagination.simple-tabler');

        // Bind in routes the uid or alias param instead of the default.
        $this->app['router']->bind('trackable', function ($value) {
            return Trackable::where('uid', $value)
                ->orWhere('alias', $value)
                ->firstOrFail();
        });
    }
}
