<?php

namespace App\Providers;

use App\Docs\DocsManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class DocumentationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(DocsManager::class, function () {
            return new DocsManager;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        RateLimiter::for('search', function (Request $request) {
            return [
                Limit::perMinute(30)->by($request->ip()),
                Limit::perSecond(4)->by($request->ip()),
            ];
        });
    }
}
