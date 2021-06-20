<?php

namespace Mtu\Lockage;

use Illuminate\Support\ServiceProvider;

class LockageServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/lockage.php' => config_path('lockage1.php')
        ]);
    }

    public function register()
    {

    }
}
