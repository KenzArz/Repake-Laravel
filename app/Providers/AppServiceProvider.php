<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Database\Eloquent\Model;
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
        // Tangkap N+1 di lingkungan non-produksi (aturan db-performance).
        Model::preventLazyLoading(! app()->isProduction());

        //for ngrok or tunneling, force https scheme for all generated URLs
        //nonactive in local dev, but needed for production behind reverse proxy
        if (request()->hasHeader('x-forwarded-proto') || app()->environment('local')) {
            URL::forceScheme('https');
        }
    }
}
