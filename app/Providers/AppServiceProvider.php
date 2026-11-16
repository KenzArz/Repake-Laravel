<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
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

        // Resources API tidak membungkus ulang dalam key "data"; envelope
        // { data, message, meta } disusun manual lewat App\Support\ApiResponse.
        JsonResource::withoutWrapping();
    }
}
