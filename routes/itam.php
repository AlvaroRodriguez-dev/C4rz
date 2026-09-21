<?php

use App\Http\Controllers\Itam\ItSolicitudController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('itam')->name('itam.')->group(function () {
    Route::middleware(['role_or_permission:SIS-ADMIN|it.solicitudes.view'])->group(function () {
        Route::view('/', 'itam.index')->name('index');
    });

    Route::middleware(['role_or_permission:SIS-ADMIN|it.solicitudes.create'])->group(function () {
        Route::get('/solicitudes/crear', [ItSolicitudController::class, 'create'])->name('solicitudes.create');
        Route::post('/solicitudes', [ItSolicitudController::class, 'store'])->name('solicitudes.store');
        Route::get('/solicitudes/personal/buscar', [ItSolicitudController::class, 'buscarPersonal'])->name('solicitudes.personal.buscar');
    });

    Route::middleware(['role_or_permission:SIS-ADMIN|it.solicitudes.view'])->group(function () {
        Route::get('/solicitudes', [ItSolicitudController::class, 'index'])->name('solicitudes.index');
        Route::get('/solicitudes/{solicitud}', [ItSolicitudController::class, 'show'])->name('solicitudes.show');
    });
});
