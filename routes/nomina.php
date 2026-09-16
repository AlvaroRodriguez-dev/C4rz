<?php

use App\Http\Controllers\NominaPersonalController;
use Illuminate\Support\Facades\Route;

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
    });
