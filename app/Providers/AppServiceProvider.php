<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Opcodes\LogViewer\Facades\LogViewer;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment('local')) {
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        // LogViewer::auth(function ($request) {
        //     return $request->user()
        //         && in_array($request->user()->email, [
        //             'platform.admin@osboha.com',
        //             'p92ahmed@gmail.com',
        //         ]);
        // });

        // date_default_timezone_set('Europe/Lisbon');


    }
}
