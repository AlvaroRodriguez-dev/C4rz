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

    Route::middleware(['permission:wms.configurar'])->prefix('configurar')->name('configurar.')->group(function () {
        Route::get('/', [WmsConfigController::class, 'index'])->name('index');
        Route::get('/crear', [WmsConfigController::class, 'create'])->name('create');
        Route::post('/', [WmsConfigController::class, 'store'])->name('store');
        Route::delete('/{codigo}', [WmsConfigController::class, 'destroy'])->name('destroy');
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

    Route::middleware(['permission:wms.pallet.ver'])->prefix('pallet-ver')->name('pallet.ver.')->group(function () {
        Route::get('/', [WmsPalletVerController::class, 'index'])->name('index');
        Route::get('/pallets/buscar', [WmsPalletVerController::class, 'buscarPallets'])->name('pallets.buscar');
        Route::get('/pallet/{pallet}', [WmsPalletVerController::class, 'contenidoPallet'])->name('pallet.contenido');
        Route::get('/ubicacion/{galpon}/{ubicacion}', [WmsPalletVerController::class, 'contenidoUbicacion'])->name('ubicacion.contenido');
    });

    Route::middleware(['permission:wms.reubicacion'])->prefix('reubicacion')->name('reubicacion.')->group(function () {
        Route::get('/', [WmsReubicacionController::class, 'index'])->name('index');
        Route::get('/pallet/{pallet}/contenido', [WmsReubicacionController::class, 'contenidoPallet'])->name('pallet.contenido');
        Route::get('/pallet/{pallet}/ubicacion', [WmsReubicacionController::class, 'ubicacionDePallet'])->name('pallet.ubicacion');
        Route::post('/pallet-completo', [WmsReubicacionController::class, 'storePalletCompleto'])->name('pallet-completo');
        Route::post('/completar-pallet', [WmsReubicacionController::class, 'storeCompletarPallet'])->name('completar-pallet');
    });

    Route::middleware(['permission:wms.ubicacion.ver'])->prefix('ubicacion-ver')->name('ubicacion.ver.')->group(function () {
        Route::get('/', [WmsPalletVerController::class, 'indexUbicacion'])->name('index');
        Route::get('/ubicaciones/buscar', [WmsPalletVerController::class, 'buscarUbicaciones'])->name('ubicaciones.buscar');
    });

    Route::middleware(['permission:wms.kardex'])->prefix('kardex')->name('kardex.')->group(function () {
        Route::get('/', [WmsKardexController::class, 'index'])->name('index');
        Route::get('/producto/{codigo}/lotes', [WmsKardexController::class, 'lotes'])->name('lotes');
        Route::get('/producto/{codigo}/galpones', [WmsKardexController::class, 'galpones'])->name('galpones');
        Route::get('/producto/{codigo}/ubicaciones', [WmsKardexController::class, 'ubicaciones'])->name('ubicaciones');
        Route::get('/producto/{codigo}/pallets', [WmsKardexController::class, 'pallets'])->name('pallets');
        Route::get('/reporte', [WmsKardexController::class, 'reporte'])->name('reporte');
    });

    Route::middleware(['permission:wms.ingresos.ajuste'])->prefix('ingresos-ajuste')->name('ingresos.ajuste.')->group(function () {
        Route::get('/', [WmsIngresoAjusteController::class, 'create'])->name('create');
        Route::get('/formatos/buscar', [WmsIngresoAjusteController::class, 'buscarFormatos'])->name('formatos.buscar');
        Route::get('/productos/buscar', [WmsIngresoAjusteController::class, 'buscarProductos'])->name('productos.buscar');
        Route::get('/producto/{codigo}/limite', [WmsIngresoAjusteController::class, 'obtenerLimite'])->name('producto.limite');
        Route::post('/', [WmsIngresoAjusteController::class, 'store'])->name('store');
    });

    Route::middleware(['permission:wms.ordenes.trabajo'])->prefix('ordenes-trabajo')->name('ordenes.trabajo.')->group(function () {
        Route::get('/', [WmsOrdenTrabajoController::class, 'index'])->name('index');
        Route::get('/pendientes', [WmsOrdenTrabajoController::class, 'pendientes'])->name('pendientes');
        Route::post('/{ordenTrabajo}/pallet/{pallet}/chequear', [WmsOrdenTrabajoController::class, 'marcarPallet'])->name('marcar-pallet');
    });

    Route::middleware(['permission:wms.excepciones.despacho'])->prefix('excepciones-despacho')->name('excepciones.despacho.')->group(function () {
        Route::get('/', [WmsExcepcionDespachoController::class, 'index'])->name('index');
        Route::get('/buscar', [WmsExcepcionDespachoController::class, 'buscar'])->name('buscar');
    });

    Route::middleware(['permission:wms.ordenes.trabajo'])->prefix('ordenes-trabajo')->name('ordenes.trabajo.')->group(function () {
        Route::get('/', [WmsOrdenTrabajoController::class, 'index'])->name('index');
        Route::get('/pendientes', [WmsOrdenTrabajoController::class, 'pendientes'])->name('pendientes');
        Route::post('/{ordenTrabajo}/pallet/{pallet}/chequear', [WmsOrdenTrabajoController::class, 'marcarPallet'])->name('marcar-pallet');
        Route::get('/{ordenTrabajo}/imprimir', [WmsOrdenTrabajoController::class, 'imprimir'])->name('imprimir');

        Route::get('/ver', [WmsOrdenTrabajoController::class, 'verIndex'])->name('ver.index');
        Route::get('/ver/buscar', [WmsOrdenTrabajoController::class, 'buscarHistorial'])->name('ver.buscar');
    });
    Route::middleware(['permission:wms.reporte.despacho'])->prefix('reporte-despacho')->name('reporte.despacho.')->group(function () {
        Route::get('/', [WmsReporteDespachoController::class, 'index'])->name('index');
        Route::get('/notas/buscar', [WmsReporteDespachoController::class, 'buscarNotas'])->name('notas.buscar');
        Route::get('/{tipoRegistro}/{idRegistro}/detalle', [WmsReporteDespachoController::class, 'detalle'])->name('detalle');
        Route::get('/{tipoRegistro}/{idRegistro}/pdf', [WmsReporteDespachoController::class, 'pdf'])->name('pdf');
    });
});

//--------- COMERCIAL -----------------

Route::prefix('comercial')->name('comercial.')->middleware('auth')->group(function () {
    Route::middleware(['permission:sis.comercial.agencias'])->group(function () {
        Route::resource('agencias', AgenciaController::class)->except(['show']);
    });

    Route::middleware(['permission:sis.comercial.contactos'])->group(function () {
        Route::resource('contactos', ComercialContactoController::class);
        Route::patch('contactos/{id}/restore', [ComercialContactoController::class, 'restore'])->name('contactos.restore');
    });
});

// ── RRHH > Asistencia ────────────────────────────────────────────
Route::prefix('asistencia')->name('asistencia.')->middleware('auth')->group(function () {

    // Registro de asistencia (todos los usuarios con license)
    Route::get('/registro',   [AsistenciaAppController::class, 'index'])->name('registro');
    Route::post('/registrar', [AsistenciaAppController::class, 'registrar'])->name('registrar');

    // Mis marcajes (rol empleado)
    Route::get('/mis-marcajes', [AsistenciaAppController::class, 'misMarcajes'])->name('mis-marcajes');

    // Reporte admin (rol RRHH-ADMIN)
    Route::match(
        ['get', 'post'],
        '/reporte',
        [AsistenciaReporteController::class, 'index']
    )->name('reporte');
});


// ── RRHH > Agencias ──────────────────────────────────────────────

Route::prefix('rrhh/agencias')->name('rrhh.agencias.')->middleware('auth')->group(function () {
    Route::get('/',                 [RrhhAgenciasController::class, 'index'])->name('index');
    Route::get('/crear',            [RrhhAgenciasController::class, 'create'])->name('create');
    Route::post('/',                [RrhhAgenciasController::class, 'store'])->name('store');
    Route::get('/{agencia}/editar', [RrhhAgenciasController::class, 'edit'])->name('edit');
    Route::put('/{agencia}',        [RrhhAgenciasController::class, 'update'])->name('update');
    Route::delete('/{agencia}',     [RrhhAgenciasController::class, 'destroy'])->name('destroy');
    Route::get('/asignaciones',     [RrhhAgenciasController::class, 'asignaciones'])->name('asignaciones');
    Route::post('/asignaciones',    [RrhhAgenciasController::class, 'guardarAsignacion'])->name('asignaciones.guardar');
});

// Asignar license (admin)
Route::post(
    '/usuarios/{user}/license',
    [RegisteredUserController::class, 'asignarLicense']
)->name('usuarios.license')->middleware('auth');

//PRUEBAS BIOMETRICO (rutas de depuración - solo SIS-ADMIN)

Route::middleware(['auth', 'role:SIS-ADMIN'])->group(function () {

    Route::get('/debug-u560/{id}', function ($id) {
        $bio = \App\Models\Biometrico::findOrFail($id);

        try {
            $zk = new \App\Lib\ZKTecoClient(
                $bio->ip,
                $bio->puerto ?? 4370,
                $bio->timeout ?? 10
            );

            if (!$zk->connect()) {
                return response()->json(['error' => 'No se pudo conectar']);
            }

            $zk->disableDevice();
            $registros = $zk->getAttendance();
            $zk->enableDevice();
            $zk->disconnect();

            // Limpiar encoding para evitar error JSON
            $limpiar = function ($valor) {
                if (is_string($valor)) {
                    return mb_convert_encoding($valor, 'UTF-8', 'UTF-8,ISO-8859-1,CP1252');
                }
                return $valor;
            };

            $muestra = array_slice($registros, 0, 5, true);
            $muestraLimpia = [];
            foreach ($muestra as $key => $registro) {
                foreach ($registro as $campo => $valor) {
                    $muestraLimpia[$key][$campo] = $limpiar($valor);
                }
            }

            return response()->json([
                'total'                => count($registros),
                'muestra'              => $muestraLimpia,
                'keys_primer_registro' => !empty($registros) ? array_keys(reset($registros)) : [],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error'  => $e->getMessage(),
                'clase'  => get_class($e),
                'linea'  => $e->getLine(),
            ]);
        }
    });

    Route::get('/debug-bio-conteo', function () {
        $biometricos = \App\Models\Biometrico::orderBy('agencia')->get();

        $resultados  = [];

        foreach ($biometricos as $bio) {
            $resultado = [
                'id'          => $bio->id,
                'agencia'     => $bio->agencia,
                'descripcion' => $bio->descripcion,
                'ip'          => $bio->ip,
                'timeout'     => $bio->timeout ?? 10,
                'conexion'    => false,
                'registros_dispositivo' => null,
                'registros_bd'          => null,
                'error'       => null,
            ];

            // ── Registros ya guardados en BD ─────────────────────────────
            $resultado['registros_bd'] = \App\Models\BioAsistencia::where('biometrico_id', $bio->id)->count();

            // ── Conectar al dispositivo y contar registros ───────────────
            try {
                $zk = new \App\Lib\ZKTecoClient(
                    $bio->ip,
                    $bio->puerto  ?? 4370,
                    $bio->timeout ?? 10
                );

                if (!$zk->connect()) {
                    $resultado['error'] = 'No se pudo conectar';
                } else {
                    $resultado['conexion'] = true;
                    $zk->disableDevice();
                    $registros = $zk->getAttendance();
                    $zk->enableDevice();
                    $zk->disconnect();
                    $resultado['registros_dispositivo'] = count($registros);
                }
            } catch (\Throwable $e) {
                try {
                    $zk->enableDevice();
                } catch (\Throwable) {
                }
                try {
                    $zk->disconnect();
                } catch (\Throwable) {
                }
                $resultado['error'] = $e->getMessage();
            }

            $resultados[] = $resultado;
        }

        return response()->json([
            'fecha'       => now()->format('d/m/Y H:i:s'),
            'biometricos' => $resultados,
        ]);
    });
});

// Publico (sin auth)
Route::get('/tarjeta/{uuid}', [TarjetaPublicaController::class, 'show'])->name('tarjeta.show');
Route::get('/tarjeta/{uuid}/vcard', [TarjetaPublicaController::class, 'vcard'])->name('tarjeta.vcard');

require __DIR__ . '/auth.php';
