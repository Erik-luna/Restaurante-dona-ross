<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // esta parte sera para el https para que no salga error de formularios en el navegador
        if ($this->app->environment('production')) {
            URL::forceScheme('https');        
        }
    }
}