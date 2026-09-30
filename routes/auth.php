<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\WelcomeController;
use App\Http\Controllers\Wms\WmsIngresoController;
use App\Http\Controllers\Wms\WmsPaletizacionController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [WelcomeController::class, 'index'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)->name('verification.notice');
    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])->middleware('throttle:6,1')->name('verification.send');
    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);
    Route::put('password', [PasswordController::class, 'update'])->name('password.update');
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

// Flujo complementario WMS: recupera HUs creados por Paletización.
// No reemplaza las rutas existentes de ingreso ni modifica wms_ingreso.
Route::middleware(['auth', 'permission:wms.ingresos.create'])
    ->prefix('wms/ingresos/paletizados')
    ->name('wms.ingresos.paletizados.')
    ->group(function () {
        Route::get('/', [WmsIngresoController::class, 'paletizados'])->name('index');
        Route::get('/buscar', [WmsIngresoController::class, 'buscarPaletizados'])->name('buscar');
        Route::get('/{entrega}/detalle', [WmsIngresoController::class, 'detallePaletizado'])->name('detalle');
        Route::post('/{entrega}/ubicar', [WmsIngresoController::class, 'ubicarPaletizado'])->name('ubicar');
    });

// Revision de registros ya paletizados y sus pallets/QR.
Route::middleware(['auth', 'permission:wms.produccion.paletizar'])
    ->prefix('wms/paletizacion')
    ->name('wms.paletizacion.')
    ->group(function () {
        Route::get('/revision', [WmsPaletizacionController::class, 'revision'])->name('revision');
        Route::get('/revision/buscar', [WmsPaletizacionController::class, 'buscarRevision'])->name('revision.buscar');
        Route::get('/revision/{entrega}', [WmsPaletizacionController::class, 'revisionShow'])->name('revision.show');
    });
