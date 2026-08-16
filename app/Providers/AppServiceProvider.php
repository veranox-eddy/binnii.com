<?php

namespace App\Providers;

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
        // Schema owner is the app.binnii.com repo: this project has NO
        // migrations of its own and deployment MUST NOT run migrate. The
        // console repo's migrations are only mounted for the test suite,
        // which builds its throwaway sqlite schema from them.
        if ($this->app->runningUnitTests()) {
            $this->loadMigrationsFrom(base_path('../app.binnii.com/database/migrations'));
        }
    }
}
