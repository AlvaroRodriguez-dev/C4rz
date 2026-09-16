<?php

use App\Http\Controllers\Itam\ItSolicitudController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('itam')->name('itam.')->group(function () {
    Route::middleware(['permission:it.solicitudes.view'])->group(function () {
        Route::get('/solicitudes', [ItSolicitudController::class, 'index'])->name('solicitudes.index');
        Route::get('/solicitudes/{solicitud}', [ItSolicitudController::class, 'show'])->name('solicitudes.show');
    });

    Route::middleware(['permission:it.solicitudes.create'])->group(function () {
        Route::get('/solicitudes/crear', [ItSolicitudController::class, 'create'])->name('solicitudes.create');
        Route::post('/solicitudes', [ItSolicitudController::class, 'store'])->name('solicitudes.store');
        Route::get('/solicitudes/personal/buscar', [ItSolicitudController::class, 'buscarPersonal'])->name('solicitudes.personal.buscar');
    });
});
