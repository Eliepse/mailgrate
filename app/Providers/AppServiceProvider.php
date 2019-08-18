<?php

namespace App\Providers;

use Eliepse\Imap\AccountPasswordManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public $singletons = [
        AccountPasswordManager::class => AccountPasswordManager::class,
    ];


    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }


    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
