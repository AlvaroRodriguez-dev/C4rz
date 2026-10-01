<?php

use App\Http\Controllers\Admin\UserRoleController;
use App\Http\Controllers\AsistenciaAppController;
use App\Http\Controllers\AsistenciaReporteController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BiometricoController;
use App\Http\Controllers\Comercial\AgenciaController;
use App\Http\Controllers\Comercial\ComercialContactoController;
use App\Http\Controllers\Comercial\TarjetaPublicaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RrhhAgenciaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VerificarBdController;
use App\Http\Controllers\MigrarContablesController;
use App\Http\Controllers\MigrarInvController;
use App\Http\Controllers\NovedadController;
use App\Http\Controllers\RrhhAgenciasController;
use App\Http\Controllers\WelcomeController;
use App\Http\Controllers\Wms\WmsConfigController;
use App\Http\Controllers\Wms\WmsAlmacenController;
use App\Http\Controllers\Wms\WmsGalponController;
use App\Http\Controllers\Wms\WmsGalponRangoController;
use App\Http\Controllers\Wms\WmsUbicacionController;
use App\Http\Controllers\Wms\WmsUsuarioAlmacenController;
use App\Http\Controllers\Wms\WmsExcepcionDespachoController;
use App\Http\Controllers\Wms\WmsIngresoAjusteController;
use App\Http\Controllers\Wms\WmsIngresoController;
use App\Http\Controllers\Wms\WmsIngresoVerController;
use App\Http\Controllers\Wms\WmsInventarioController;
use App\Http\Controllers\Wms\WmsKardexController;
use App\Http\Controllers\Wms\WmsOrdenTrabajoController;
use App\Http\Controllers\Wms\WmsPalletVerController;
use App\Http\Controllers\Wms\WmsReporteDespachoController;
use App\Http\Controllers\Wms\WmsReubicacionController;
use App\Http\Controllers\Wms\WmsSalidaController;
use App\Http\Controllers\Wms\WmsSalidaVerController;
use App\Http\Controllers\Wms\WmsTicketLoteController;
use App\Http\Controllers\Wms\WmsProduccionVerificacionController;
use App\Http\Controllers\Wms\WmsLiberacionProduccionController;
use App\Http\Controllers\Wms\WmsPaletizacionController;


Route::get('/', [WelcomeController::class, 'index'])->name('welcome');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// CRUD DE ASIGNACION DE ROLES Y PERMISOS (solo SIS-ADMIN)
Route::middleware(['auth', 'role:SIS-ADMIN'])
    ->prefix('admin/usuarios')
    ->name('admin.usuarios.')
    ->group(function () {
        Route::get('/', [UserRoleController::class, 'index'])->name('index');
        Route::get('/{user}/editar', [UserRoleController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserRoleController::class, 'update'])->name('update');
    });

// PERFIL - cualquier usuario autenticado gestiona su propio perfil
Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ── SIS: Verificar BD / Migraciones ─────────────────────────────────────
Route::middleware(['auth', 'permission:sis.verificar-bd'])->group(function () {
    Route::get('/verificar-bd', [VerificarBdController::class, 'index'])->name('verificar-bd.index');
    Route::post('/verificar-bd', [VerificarBdController::class, 'listar'])->name('verificar-bd.listar');
    Route::post('/verificar-bd/glosa', [VerificarBdController::class, 'verificarGlosa'])->name('verificar-bd.glosa');
    Route::post('/verificar-bd/movimiento', [VerificarBdController::class, 'verificarMovimiento'])->name('verificar-bd.movimiento');
    Route::get('/verificar-bd/detalle', [VerificarBdController::class, 'detalle'])->name('verificar-bd.detalle');
    Route::post('/verificar-bd/actualizar', [VerificarBdController::class, 'actualizarRegistro'])->name('verificar-bd.actualizar');
    Route::post('/verificar-bd/actualizar-todos', [VerificarBdController::class, 'actualizarTodos'])->name('verificar-bd.actualizar-todos');
});

Route::middleware(['auth', 'permission:sis.migrar-contables'])->group(function () {
    Route::get('/migrar-contables', [MigrarContablesController::class, 'index'])->name('migrar.contables.index');
    Route::post('/migrar-contables', [MigrarContablesController::class, 'ejecutar'])->name('migrar.contables.ejecutar');
});

Route::middleware(['auth', 'permission:sis.migrar-inv'])->group(function () {
    Route::get('/migrar-inv', [MigrarInvController::class, 'index'])->name('migrar.inv.index');
    Route::post('/migrar-inv', [MigrarInvController::class, 'ejecutar'])->name('migrar.inv.ejecutar');
});

// ── Biométricos ──────────────────────────────────────────────────────────
Route::middleware(['auth'])->prefix('biometricos')->name('biometricos.')->group(function () {

    // Recuperar datos
    Route::middleware(['permission:rrhh.recuperar-datos'])->group(function () {
        Route::get('/recuperar',            [BiometricoController::class, 'recuperar'])->name('recuperar');
        Route::post('/recuperar/usuarios',  [BiometricoController::class, 'recuperarUsuarios'])->name('recuperar.usuarios');
        Route::post('/recuperar/registros', [BiometricoController::class, 'recuperarRegistros'])->name('recuperar.registros');
    });

    // Importar USB
    Route::middleware(['permission:rrhh.importar-usb'])->group(function () {
        Route::get('/importar',             [BiometricoController::class, 'importar'])->name('importar');
        Route::post('/importar/procesar',   [BiometricoController::class, 'procesarImportacion'])->name('importar.procesar');
    });

    // Reporte
    Route::middleware(['permission:rrhh.reporte'])->group(function () {
        Route::get('/reporte',              [BiometricoController::class, 'reporte'])->name('reporte');
        Route::post('/reporte/usuarios',    [BiometricoController::class, 'usuariosPorBiometrico'])->name('reporte.usuarios');
        Route::post('/reporte/generar',     [BiometricoController::class, 'generarReporte'])->name('reporte.generar');
    });

    // Novedades de asistencia
    Route::middleware(['permission:rrhh.novedades'])->group(function () {
        Route::get('/novedades',                [NovedadController::class, 'index'])->name('novedades');
        Route::post('/novedades/generar',       [NovedadController::class, 'generar'])->name('novedades.generar');
        Route::post('/novedades/buscar-boleta', [NovedadController::class, 'buscarBoleta'])->name('novedades.buscar-boleta');
        Route::post('/novedades/ver-boleta',    [NovedadController::class, 'verBoleta'])->name('novedades.ver-boleta');
    });
});

//-------- WMS ----------

Route::middleware(['auth'])->prefix('wms')->name('wms.')->group(function () {

    // Menú principal WMS: accesible a cualquiera de los 4 roles con acceso a WMS
    Route::middleware(['role_or_permission:WMS-ADMIN|WMS-ALMACEN|WMS-MONTACARGA|SIS-ADMIN'])->group(function () {
        Route::get('/', function () {
            return view('wms.index');
        })->name('index');
    });

    Route::middleware(['permission:wms.configurar'])->prefix('almacenes')->name('almacenes.')->group(function () {
        Route::get('/', [WmsAlmacenController::class, 'index'])->name('index');
        Route::get('/crear', [WmsAlmacenController::class, 'create'])->name('create');
        Route::post('/', [WmsAlmacenController::class, 'store'])->name('store');
        Route::get('/{almacen}/editar', [WmsAlmacenController::class, 'edit'])->name('edit');
        Route::put('/{almacen}', [WmsAlmacenController::class, 'update'])->name('update');
    });

    Route::middleware(['permission:wms.configurar'])->prefix('galpones')->name('galpones.')->group(function () {
        Route::get('/', [WmsGalponController::class, 'index'])->name('index');
        Route::get('/crear', [WmsGalponController::class, 'create'])->name('create');
        Route::post('/', [WmsGalponController::class, 'store'])->name('store');
        Route::get('/{galpon}/rangos', [WmsGalponRangoController::class, 'index'])->name('rangos.index');
        Route::get('/{galpon}/rangos/crear', [WmsGalponRangoController::class, 'create'])->name('rangos.create');
        Route::post('/{galpon}/rangos', [WmsGalponRangoController::class, 'store'])->name('rangos.store');
        Route::get('/{galpon}/editar', [WmsGalponController::class, 'edit'])->name('edit');
        Route::put('/{galpon}', [WmsGalponController::class, 'update'])->name('update');
    });

    Route::middleware(['permission:wms.configurar'])->prefix('ubicaciones')->name('ubicaciones.')->group(function () {
        Route::get('/', [WmsUbicacionController::class, 'index'])->name('index');
    });

    Route::middleware(['permission:wms.ingresos.create'])->prefix('maestros')->name('maestros.')->group(function () {
        Route::get('/ubicaciones/opciones', [WmsUbicacionController::class, 'opciones'])->name('ubicaciones.opciones');
        Route::get('/ubicaciones/galpon/{galpon}', [WmsUbicacionController::class, 'porGalpon'])->name('ubicaciones.por-galpon');
    });

    Route::middleware(['permission:wms.configurar'])->prefix('usuario-almacenes')->name('usuario-almacenes.')->group(function () {
        Route::get('/', [WmsUsuarioAlmacenController::class, 'index'])->name('index');
        Route::get('/{user}/editar', [WmsUsuarioAlmacenController::class, 'edit'])->name('edit');
        Route::put('/{user}', [WmsUsuarioAlmacenController::class, 'update'])->name('update');
    });

    Route::middleware(['permission:wms.configurar'])->prefix('configurar')->name('configurar.')->group(function () {
        Route::get('/', [WmsConfigController::class, 'index'])->name('index');
        Route::get('/crear', [WmsConfigController::class, 'create'])->name('create');
        Route::post('/', [WmsConfigController::class, 'store'])->name('store');
        Route::delete('/{codigo}', [WmsConfigController::class, 'destroy'])->name('destroy');
    });

    Route::middleware(['permission:wms.produccion.verificar'])->prefix('produccion-liberacion')->name('produccion.liberacion.')->group(function () {
        Route::get('/', [WmsLiberacionProduccionController::class, 'create'])->name('create');
        Route::get('/productos/buscar', [WmsLiberacionProduccionController::class, 'buscarProductos'])->name('productos.buscar');
        Route::get('/{entrega}/pallets', [WmsLiberacionProduccionController::class, 'pallets'])->name('pallets');
        Route::post('/{entrega}/pallets/generar', [WmsLiberacionProduccionController::class, 'generarPallets'])->name('pallets.generar');
        Route::post('/', [WmsLiberacionProduccionController::class, 'store'])->name('store');
    });

    Route::middleware(['permission:wms.produccion.paletizar'])
        ->prefix('paletizacion')
        ->name('paletizacion.')
        ->group(function () {
            Route::get('/', [WmsPaletizacionController::class, 'index'])->name('index');
            Route::get('/revision', [WmsPaletizacionController::class, 'revision'])->name('revision');
            Route::get('/revision/buscar', [WmsPaletizacionController::class, 'buscarRevision'])->name('revision.buscar');
            Route::get('/revision/{entrega}', [WmsPaletizacionController::class, 'revisionShow'])->name('revision.show');
            Route::get('/buscar', [WmsPaletizacionController::class, 'buscar'])->name('buscar');
            Route::get('/{entrega}', [WmsPaletizacionController::class, 'show'])->name('show');
            Route::post('/{entrega}', [WmsPaletizacionController::class, 'store'])->name('store');
        });

    Route::middleware(['permission:wms.produccion.verificar'])->prefix('produccion-verificacion')->name('produccion.verificacion.')->group(function () {
        Route::get('/', [WmsProduccionVerificacionController::class, 'index'])->name('index');
        Route::get('/buscar', [WmsProduccionVerificacionController::class, 'buscar'])->name('buscar');
        Route::get('/{entrega}', [WmsProduccionVerificacionController::class, 'show'])->name('show');
        Route::post('/{entrega}/iniciar', [WmsProduccionVerificacionController::class, 'iniciar'])->name('iniciar');
        Route::post('/{entrega}/conciliar', [WmsProduccionVerificacionController::class, 'conciliar'])->name('conciliar');
        Route::patch('/detalle/{detalle}/cantidad', [WmsProduccionVerificacionController::class, 'cantidad'])->name('detalle.cantidad');
    });

    Route::middleware(['permission:wms.ingresos.create'])->prefix('ingresos')->name('ingresos.')->group(function () {
        Route::get('/crear', [WmsIngresoController::class, 'create'])->name('create');
        Route::get('/notas/buscar', [WmsIngresoController::class, 'buscarNotas'])->name('notas.buscar');
        Route::get('/notas/{rdocum}/detalle', [WmsIngresoController::class, 'detalleNota'])->name('notas.detalle');
        Route::post('/', [WmsIngresoController::class, 'store'])->name('store');
    });

    Route::middleware(['permission:wms.salidas.create'])->prefix('salidas')->name('salidas.')->group(function () {
        Route::get('/crear', [WmsSalidaController::class, 'create'])->name('create');
        Route::get('/notas/buscar', [WmsSalidaController::class, 'buscarNotas'])->name('notas.buscar');
        Route::get('/notas/{id}/detalle', [WmsSalidaController::class, 'detalleNota'])->name('notas.detalle');
        Route::post('/', [WmsSalidaController::class, 'store'])->name('store');
        Route::get('/{tipoRegistro}/{idRegistro}/ticket-variacion-lote', [WmsTicketLoteController::class, 'descargar'])
            ->name('ticket-variacion-lote');
        Route::get('/lotes-alternativos', [WmsSalidaController::class, 'lotesAlternativos'])->name('lotes-alternativos');
        Route::get('/ubicaciones-por-lote', [WmsSalidaController::class, 'ubicacionesPorLote'])->name('ubicaciones-por-lote');
        Route::get('/distribucion-automatica', [WmsSalidaController::class, 'distribucionAutomatica'])->name('distribucion-automatica');
    });

    Route::middleware(['permission:wms.inventario'])->prefix('inventario')->name('inventario.')->group(function () {
        Route::get('/', [WmsInventarioController::class, 'index'])->name('index');
        Route::get('/productos/buscar', [WmsInventarioController::class, 'buscarProductos'])->name('productos.buscar');
        Route::get('/{codigo}/saldos', [WmsInventarioController::class, 'saldos'])->name('saldos');
    });

    Route::middleware(['permission:wms.ingresos.ver'])->prefix('ingresos-ver')->name('ingresos.ver.')->group(function () {
        Route::get('/', [WmsIngresoVerController::class, 'index'])->name('index');
        Route::get('/buscar', [WmsIngresoVerController::class, 'buscar'])->name('buscar');
    });

    Route::middleware(['permission:wms.salidas.ver'])->prefix('salidas-ver')->name('salidas.ver.')->group(function () {
        Route::get('/', [WmsSalidaVerController::class, 'index'])->name('index');
        Route::get('/buscar', [WmsSalidaVerController::class, 'buscar'])->name('buscar');
    });
