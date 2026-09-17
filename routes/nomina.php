<?php

use App\Http\Controllers\NominaConfiguracionLaboralController;
use App\Http\Controllers\NominaPersonalController;
use Illuminate\Support\Facades\Route;

/*
 * Este archivo se carga desde bootstrap/app.php mediante el callback `then`.
 * A diferencia de routes/web.php, esas rutas no heredan automáticamente
 * el middleware `web`. Lo declaramos explícitamente para que la sesión
 * autenticada esté disponible y auth no redirija al Dashboard/Login.
 */
Route::middleware(['web'])
    ->group(function () {
        Route::middleware(['auth'])
            ->prefix('nomina')
            ->name('nomina.')
            ->group(function () {
                Route::get('/personal', [NominaPersonalController::class, 'index'])
                    ->name('personal.index');

                Route::post('/personal', [NominaPersonalController::class, 'store'])
                    ->name('personal.store');

                Route::patch('/personal/{persona}/estado', [NominaPersonalController::class, 'toggle'])
                    ->name('personal.toggle');

                Route::middleware(['permission:rrhh.nomina'])->group(function () {
                    Route::get('/configuraciones-laborales', [NominaConfiguracionLaboralController::class, 'index'])
                        ->name('configuraciones-laborales.index');

                    Route::get('/configuraciones-laborales/{personal}', [NominaConfiguracionLaboralController::class, 'show'])
                        ->name('configuraciones-laborales.show');

                    Route::post('/configuraciones-laborales/{license}/sincronizar', [NominaConfiguracionLaboralController::class, 'sincronizar'])
                        ->name('configuraciones-laborales.sincronizar');
                });
            });
    });
