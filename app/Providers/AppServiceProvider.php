<?php

namespace App\Providers;

use App\Http\Controllers\Wms\WmsPaletizacionController;
use Illuminate\Support\Facades\Route;
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
        Route::middleware(['auth', 'permission:wms.produccion.paletizar'])
            ->prefix('wms/paletizacion')
            ->name('wms.paletizacion.')
            ->group(function () {
                Route::get('/', [WmsPaletizacionController::class, 'index'])->name('index');
                Route::get('/buscar', [WmsPaletizacionController::class, 'buscar'])->name('buscar');
                Route::get('/{entrega}', [WmsPaletizacionController::class, 'show'])->name('show');
                Route::post('/{entrega}', [WmsPaletizacionController::class, 'store'])->name('store');
            });
    }
}
