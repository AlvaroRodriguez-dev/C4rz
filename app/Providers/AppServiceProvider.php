<?php

namespace App\Providers;

use App\Http\Controllers\Wms\WmsVerificacionPalletController;
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
        // Compatibilidad temporal con la URL utilizada por la vista de verificación.
        Route::middleware(['auth', 'permission:wms.produccion.verificar'])
            ->prefix('wms/produccion-liberacion')
            ->post('/{entrega}/verificacion-pallets/pallet/{hu}/confirmar', [WmsVerificacionPalletController::class, 'confirmar'])
            ->name('wms.produccion.liberacion.verificacion.pallets.confirmar.compat');
    }
}
