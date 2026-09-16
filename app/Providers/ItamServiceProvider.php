<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ItamServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(base_path('routes/itam.php'));
    }
}
