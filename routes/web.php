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