<?php

namespace App\Providers;

use DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (App::environment('production')) {
            URL::forceScheme('https');
        }

        if (env('APP_DEBUG')) {
            DB::listen(function ($query) {
                Log::info(
                    $query->sql,
                    $query->bindings,
                    $query->time
                );
            });
        }
    }
}
