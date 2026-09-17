<?php

use App\Http\Controllers\NominaConfiguracionLaboralController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('nomina/configuraciones-laborales')->name('nomina.configuraciones-laborales.')->group(function () {
    Route::get('/', [NominaConfiguracionLaboralController::class, 'index'])->name('index');
    Route::get('/{personal}', [NominaConfiguracionLaboralController::class, 'show'])->name('show');
    Route::post('/{license}/sincronizar', [NominaConfiguracionLaboralController::class, 'sincronizar'])->name('sincronizar');
});
