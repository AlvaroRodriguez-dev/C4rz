<?php

use App\Http\Controllers\NominaConfiguracionLaboralController;
use App\Http\Controllers\NominaConfiguracionSalarialController;
use App\Http\Controllers\NominaConceptoController;
use App\Http\Controllers\NominaPersonalConceptoController;
use App\Http\Controllers\NominaPersonalController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function () {
    Route::middleware(['auth'])->prefix('nomina')->name('nomina.')->group(function () {
        Route::get('/personal', [NominaPersonalController::class, 'index'])->name('personal.index');
        Route::post('/personal', [NominaPersonalController::class, 'store'])->name('personal.store');
        Route::patch('/personal/{persona}/estado', [NominaPersonalController::class, 'toggle'])->name('personal.toggle');

        Route::middleware(['permission:rrhh.nomina'])->group(function () {
            Route::get('/configuraciones-laborales', [NominaConfiguracionLaboralController::class, 'index'])->name('configuraciones-laborales.index');
            Route::get('/configuraciones-laborales/{personal}', [NominaConfiguracionLaboralController::class, 'show'])->name('configuraciones-laborales.show');
            Route::post('/configuraciones-laborales/{license}/sincronizar', [NominaConfiguracionLaboralController::class, 'sincronizar'])->name('configuraciones-laborales.sincronizar');

            Route::get('/configuraciones-salariales', [NominaConfiguracionSalarialController::class, 'index'])->name('configuraciones-salariales.index');
            Route::get('/configuraciones-salariales/{personal}', [NominaConfiguracionSalarialController::class, 'show'])->name('configuraciones-salariales.show');
            Route::post('/configuraciones-salariales/{personal}', [NominaConfiguracionSalarialController::class, 'store'])->name('configuraciones-salariales.store');

            Route::get('/conceptos', [NominaConceptoController::class, 'index'])->name('conceptos.index');
            Route::get('/conceptos/create', [NominaConceptoController::class, 'create'])->name('conceptos.create');
            Route::post('/conceptos', [NominaConceptoController::class, 'store'])->name('conceptos.store');
            Route::get('/conceptos/{concepto}/edit', [NominaConceptoController::class, 'edit'])->name('conceptos.edit');
            Route::put('/conceptos/{concepto}', [NominaConceptoController::class, 'update'])->name('conceptos.update');

            Route::get('/personal-conceptos', [NominaPersonalConceptoController::class, 'index'])->name('personal-conceptos.index');
            Route::get('/personal-conceptos/create', [NominaPersonalConceptoController::class, 'create'])->name('personal-conceptos.create');
            Route::post('/personal-conceptos', [NominaPersonalConceptoController::class, 'store'])->name('personal-conceptos.store');
            Route::get('/personal-conceptos/{personalConcepto}/edit', [NominaPersonalConceptoController::class, 'edit'])->name('personal-conceptos.edit');
            Route::put('/personal-conceptos/{personalConcepto}', [NominaPersonalConceptoController::class, 'update'])->name('personal-conceptos.update');
        });
    });
});
