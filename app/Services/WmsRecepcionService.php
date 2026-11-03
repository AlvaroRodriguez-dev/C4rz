<?php

namespace App\Services;

use App\Models\WmsConfigPallet;
use App\Models\WmsIngreso;
use App\Models\WmsRecepcionBloqueo;
use App\Models\WmsRecepcionDetalle;
use App\Models\WmsRecepcionExcepcion;
use App\Services\WmsContextService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

class WmsRecepcionService
{
    /**
     * Buscar recepciones de SAS-ERP por su código.
     *
     * Solo consulta registros de tipo 4 (RECEPCIONES).
     * El código es alfanumérico y se trata siempre como texto.
     */


    public function __construct(
        private readonly WmsContextService $context,
        private readonly WmsUbicacionService $ubicacionService,
        private readonly PalletCorrelativoService $palletService
    ) {}

    public function buscar(string $termino, string $codigoAlmacen): Collection
    {
        $termino = trim($termino);
        $codigoAlmacen = trim($codigoAlmacen);

        if ($termino === '' || $codigoAlmacen === '') {
            return collect();
        }

        return DB::connection('faboce2026')
            ->table('log_registro')
            ->select([
                'id',
                'id_documento',
                'agencia',
                'destino',
                'fecha',
                'tipo_registro',
                'glosa',
            ])
            ->where('tipo_registro', 4)
            ->where('deleted_id', 0)
            ->where('agencia', $codigoAlmacen)
            ->where(function ($query) use ($termino) {
                $query->where('id', 'like', '%' . $termino . '%')
                    ->orWhere('glosa', 'like', '%' . $termino . '%');
            })
            ->orderByDesc('fecha')
            ->limit(30)
            ->get();
    }

    public function detalle(string $idRecepcion): Collection
    {
        $idRecepcion = trim($idRecepcion);

        if ($idRecepcion === '') {
            throw new InvalidArgumentException(
                'El código de recepción debe ser válido.'
            );
        }

        $codigoAlmacen = $this->context->codigo();
        $conexion = DB::connection('faboce2026');

        // Validar la recepción exacta y el almacén autorizado.
        // No utilizar LIKE en esta consulta.
        $cabecera = $conexion->table('log_registro')
            ->where('id', $idRecepcion)
            ->where('tipo_registro', 4)
            ->where('deleted_id', 0)
            ->where('agencia', $codigoAlmacen)
            ->first();

        if (!$cabecera) {
            throw new InvalidArgumentException(
                'La recepción no existe, no está disponible o no corresponde al almacén autorizado.'
            );
        }

        $detallesOrigen = $conexion->table('log_registro_detalle')
            ->select([
                'id',
                'id_registro',
                'codigo',
                'lote',
                'cantidad_despacho',
                'cantidad_factura',
                'fila',
            ])
            ->where('id_registro', $idRecepcion)
            ->where('deleted_id', 0)
            ->whereNotNull('codigo')
            ->where('codigo', '<>', '')
            ->where('cantidad_despacho', '>', 0)
            ->orderBy('id')
            ->get();

        if ($detallesOrigen->isEmpty()) {
            return collect();
        }

        // Obtener las descripciones desde el catálogo consolidado.
        $codigos = $detallesOrigen
            ->pluck('codigo')
            ->map(fn ($codigo) => trim((string) $codigo))
            ->filter()
            ->unique()
            ->values();

        $stock = DB::connection('sisinvconsolidado2026')
            ->table('stock')
            ->whereIn('CODIGO', $codigos)
            ->select('CODIGO', 'DESCRIP', 'DESCRIP1')
            ->get()
            ->keyBy('CODIGO');

        $idsDetalle = $detallesOrigen
            ->pluck('id')
            ->unique()
            ->values();


        $cantidadesAsignadas = WmsRecepcionDetalle::query()
            ->select('id_detalle_origen')
            ->selectRaw('SUM(cantidad_asignada) AS cantidad_asignada')
            ->whereIn('id_detalle_origen', $idsDetalle)
            ->whereHas('ingreso', function ($query) use ($idRecepcion) {
                $query
                    ->where('tipo_ingreso', 'recepcion')
                    ->where('doc_origen', $idRecepcion);
            })
            ->groupBy('id_detalle_origen')
            ->pluck('cantidad_asignada', 'id_detalle_origen');


        $pendientes = $detallesOrigen->map(
            function ($detalle) use ($cantidadesAsignadas) {
                $cantidadDespachada = (int) $detalle->cantidad_despacho;

                $cantidadAsignada = (int) (
                    $cantidadesAsignadas[$detalle->id] ?? 0
                );

                $cantidadPendiente =
                    $cantidadDespachada - $cantidadAsignada;

                return [
                    'id_detalle_origen' => (int) $detalle->id,
                    'codigo' => trim((string) $detalle->codigo),
                    'lote_origen' => trim((string) ($detalle->lote ?? '')),
                    'cantidad_despacho' => $cantidadDespachada,
                    'cantidad_asignada' => $cantidadAsignada,
                    'cantidad_pendiente' => $cantidadPendiente,
                    'fila' => $detalle->fila,
                ];
            }
        );

        if ($pendientes->contains(
            fn(array $item) => $item['cantidad_pendiente'] < 0
        )) {
            throw new RuntimeException(
                'La recepción contiene cantidades asignadas superiores a las despachadas. Debe revisarse antes de continuar.'
            );
        }

        return $pendientes
            ->filter(fn(array $item) => $item['cantidad_pendiente'] > 0)
            ->groupBy(
                fn(array $item) =>
                $item['codigo'] . '|' . $item['lote_origen']
            )
            ->map(function (Collection $grupo) use ($stock) {
                $primero = $grupo->first();
                $stockInfo = $stock->get($primero['codigo']);

                return [
                    'codigo' => $primero['codigo'],
                    'descrip' => $stockInfo->DESCRIP ?? null,
                    'descrip1' => $stockInfo->DESCRIP1 ?? null,
                    'lote_origen' => $primero['lote_origen'],
                    'cantidad_despacho' => $grupo->sum('cantidad_despacho'),
                    'cantidad_asignada' => $grupo->sum('cantidad_asignada'),
                    'cantidad_pendiente' => $grupo->sum('cantidad_pendiente'),
                    'detalles_origen' => $grupo->map(
                        fn(array $item) => [
                            'id_detalle_origen' => $item['id_detalle_origen'],
                            'cantidad_pendiente' => $item['cantidad_pendiente'],
                            'fila' => $item['fila'],
                        ]
                    )->values()->all(),
                ];
            })
            ->values();
    }

    public function registrar(string $idRecepcion, array $grupos): array
    {
        $idRecepcion = trim($idRecepcion);

        if ($idRecepcion === '' || empty($grupos)) {
            throw ValidationException::withMessages([
                'general' => 'Debe seleccionar una recepción y registrar al menos un pallet.',
            ]);
        }

        $usuario = $this->context->usuario();
        $almacen = $this->context->almacen();
        $codigoAlmacen = (string) $almacen->codigo;

        // Recuperar las descripciones desde el mismo catálogo utilizado por INGRESO CON NOTA.
        $codigos = collect($grupos)
            ->flatMap(fn ($grupo) => collect($grupo['items'] ?? [])->pluck('codigo'))
            ->map(fn ($codigo) => trim((string) $codigo))
            ->filter()
            ->unique()
            ->values();

        $stock = DB::connection('sisinvconsolidado2026')
            ->table('stock')
            ->whereIn('CODIGO', $codigos)
            ->select('CODIGO', 'DESCRIP', 'DESCRIP1')
            ->get()
            ->keyBy('CODIGO');

        return DB::transaction(function () use (
            $idRecepcion,
            $grupos,
            $usuario,
            $almacen,
            $codigoAlmacen,
            $stock
        ) {
            /*
         * 1. Crear o recuperar el bloqueo de la recepción.
         */
            DB::table('wms_recepcion_bloqueos')->insertOrIgnore([
                'id_recepcion' => $idRecepcion,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $bloqueo = WmsRecepcionBloqueo::query()
                ->where('id_recepcion', $idRecepcion)
                ->lockForUpdate()
                ->first();

            if (!$bloqueo) {
                throw new RuntimeException(
                    'No fue posible bloquear la recepción para su registro.'
                );
            }

            /*
         * 2. Consultar nuevamente la cabecera SAS-ERP.
         */
            $conexionOrigen = DB::connection('faboce2026');

            $cabecera = $conexionOrigen->table('log_registro')
                ->where('id', $idRecepcion)
                ->where('tipo_registro', 4)
                ->where('deleted_id', 0)
                ->where('agencia', $codigoAlmacen)
                ->first();

            if (!$cabecera) {
                throw ValidationException::withMessages([
                    'recepcion' => 'La recepción ya no está disponible o no corresponde al almacén autorizado.',
                ]);
            }

            /*
         * 3. Consultar nuevamente los detalles originales.
         */
            $detallesOrigen = $conexionOrigen
                ->table('log_registro_detalle')
                ->select([
                    'id',
                    'id_registro',
                    'codigo',
                    'lote',
                    'cantidad_despacho',
                    'fila',
                ])
                ->where('id_registro', $idRecepcion)
                ->where('deleted_id', 0)
                ->whereNotNull('codigo')
                ->where('codigo', '<>', '')
                ->where('cantidad_despacho', '>', 0)
                ->get()
                ->keyBy('id');

            if ($detallesOrigen->isEmpty()) {
                throw ValidationException::withMessages([
                    'recepcion' => 'La recepción no contiene detalles válidos para ingresar.',
                ]);
            }

            /*
         * 4. Recalcular cantidades previamente asignadas.
         */
            $idsDetalle = $detallesOrigen->keys()->values();

            $asignadasAnteriormente = WmsRecepcionDetalle::query()
                ->select('id_detalle_origen')
                ->selectRaw('SUM(cantidad_asignada) AS total_asignado')
                ->whereIn('id_detalle_origen', $idsDetalle)
                ->whereHas('ingreso', function ($query) use ($idRecepcion) {
                    $query
                        ->where('tipo_ingreso', 'recepcion')
                        ->where('doc_origen', $idRecepcion);
                })
                ->groupBy('id_detalle_origen')
                ->pluck('total_asignado', 'id_detalle_origen');

            $pendientes = [];

            foreach ($detallesOrigen as $id => $detalle) {
                $despachado = (int) $detalle->cantidad_despacho;
                $asignado = (int) ($asignadasAnteriormente[$id] ?? 0);

                if ($asignado > $despachado) {
                    throw new RuntimeException(
                        "El detalle {$id} tiene asignaciones WMS superiores a su cantidad despachada."
                    );
                }

                $pendientes[$id] = $despachado - $asignado;
            }

            /*
         * 5. Obtener capacidades de pallets.
         */
            $capacidades = WmsConfigPallet::query()
                ->pluck('cajas_x_pallet', 'codigo');

            $acumuladoSolicitud = [];
            $gruposValidados = [];
            $requiereExceso = false;
            $requiereDiscrepancia = false;

            /*
         * 6. Validar cada pallet.
         */
            foreach ($grupos as $indiceGrupo => $grupo) {
                if (empty($grupo['items']) || !is_array($grupo['items'])) {
                    throw ValidationException::withMessages([
                        "grupos.{$indiceGrupo}.items" => 'El pallet debe contener al menos un producto.',
                    ]);
                }

                $ubicacion = $this->ubicacionService->validarNormal(
                    $almacen->id,
                    $grupo['galpon'] ?? '',
                    $grupo['ubicacion'] ?? ''
                );

                $formatos = [];
                $totalPallet = 0;
                $itemsValidados = [];

                foreach ($grupo['items'] as $indiceItem => $item) {
                    $codigo = trim((string) ($item['codigo'] ?? ''));
                    $loteFisico = trim((string) ($item['clote'] ?? ''));
                    $cantidad = (int) ($item['cantidad'] ?? 0);
                    $asignaciones = $item['asignaciones'] ?? [];

                    if (
                        $codigo === '' ||
                        $loteFisico === '' ||
                        $cantidad <= 0 ||
                        !is_array($asignaciones) ||
                        empty($asignaciones)
                    ) {
                        throw ValidationException::withMessages([
                            "grupos.{$indiceGrupo}.items.{$indiceItem}" =>
                            'El producto, la cantidad y sus asignaciones son obligatorios.',
                        ]);
                    }

                    $formato = strtoupper(substr($codigo, 5, 4));
                    $formatos[] = $formato;
                    $totalPallet += $cantidad;

                    $totalAsignaciones = 0;
                    $asignacionesValidadas = [];

                    /*
 * Consolidar asignaciones repetidas del mismo detalle.
 * Esto evita duplicados en wms_recepcion_detalles.
 */
                    $asignacionesAgrupadas = [];

                    foreach ($asignaciones as $asignacion) {
                        $idDetalle = (int) ($asignacion['id_detalle_origen'] ?? 0);
                        $cantidadAsignada = (int) ($asignacion['cantidad'] ?? 0);

                        if ($idDetalle <= 0 || $cantidadAsignada <= 0) {
                            throw ValidationException::withMessages([
                                "grupos.{$indiceGrupo}.items.{$indiceItem}.asignaciones" =>
                                'Cada asignación debe tener un detalle de origen y una cantidad positiva.',
                            ]);
                        }

                        $asignacionesAgrupadas[$idDetalle] =
                            ($asignacionesAgrupadas[$idDetalle] ?? 0) + $cantidadAsignada;
                    }

                    /*
 * Validar cada detalle consolidado y calcular el excedente real.
 */
                    foreach ($asignacionesAgrupadas as $idDetalle => $cantidadAsignada) {
                        $detalle = $detallesOrigen->get($idDetalle);

                        if (!$detalle || trim((string) $detalle->codigo) !== $codigo) {
                            throw ValidationException::withMessages([
                                'asignaciones' =>
                                "El detalle {$idDetalle} no pertenece al producto {$codigo} de esta recepción.",
                            ]);
                        }

                        $totalAsignaciones += $cantidadAsignada;

                        $yaConsumidoEnSolicitud = $acumuladoSolicitud[$idDetalle] ?? 0;

                        $saldoDisponibleAntes = max(
                            0,
                            $pendientes[$idDetalle] - $yaConsumidoEnSolicitud
                        );

                        // Solo se considera excedente la cantidad que supera el saldo.
                        $cantidadExcedente = max(
                            0,
                            $cantidadAsignada - $saldoDisponibleAntes
                        );

                        $esExceso = $cantidadExcedente > 0;

                        if ($esExceso) {
                            $requiereExceso = true;

                            if (trim((string) ($item['observacion_exceso'] ?? '')) === '') {
                                throw ValidationException::withMessages([
                                    'observacion_exceso' =>
                                    "Debes justificar el exceso del detalle {$idDetalle}.",
                                ]);
                            }
                        }

                        $loteOrigen = trim((string) ($detalle->lote ?? ''));

                        // Normalizar para comparar sin diferencias de mayúsculas.
                        $loteOrigenNormalizado = strtoupper($loteOrigen);
                        $loteFisicoNormalizado = strtoupper($loteFisico);

                        $esDiscrepancia = $loteFisicoNormalizado !== $loteOrigenNormalizado;

                        if ($esDiscrepancia) {
                            $requiereDiscrepancia = true;

                            if (trim((string) ($item['observacion_lote'] ?? '')) === '') {
                                throw ValidationException::withMessages([
                                    'observacion_lote' =>
                                    "Debes justificar la discrepancia de lote del detalle {$idDetalle}.",
                                ]);
                            }
                        }

                        $acumuladoSolicitud[$idDetalle] =
                            $yaConsumidoEnSolicitud + $cantidadAsignada;

                        $asignacionesValidadas[] = [
                            'id_detalle_origen' => $idDetalle,
                            'cantidad' => $cantidadAsignada,
                            'cantidad_excedente' => $cantidadExcedente,
                            'saldo_disponible_antes' => $saldoDisponibleAntes,
                            'lote_origen' => $loteOrigen,
                            'es_exceso' => $esExceso,
                            'es_discrepancia' => $esDiscrepancia,
                        ];
                    }

                    if ($totalAsignaciones !== $cantidad) {
                        throw ValidationException::withMessages([
                            'asignaciones' =>
                            "La suma de asignaciones del producto {$codigo} no coincide con su cantidad física.",
                        ]);
                    }

                    $stockInfo = $stock->get($codigo);

                    $itemsValidados[] = [
                        'codigo' => $codigo,
                        'descrip'  => $stockInfo->DESCRIP ?? null,
                        'descrip1' => $stockInfo->DESCRIP1 ?? null,
                        'clote' => $loteFisico !== '' ? $loteFisico : null,
                        'cantidad' => $cantidad,
                        'observacion_exceso' => trim((string) ($item['observacion_exceso'] ?? '')),
                        'observacion_lote' => trim((string) ($item['observacion_lote'] ?? '')),
                        'asignaciones' => $asignacionesValidadas,
                    ];
                }

                $formatosUnicos = array_unique($formatos);

                if (count($formatosUnicos) !== 1) {
                    throw ValidationException::withMessages([
                        'pallet' => 'Un pallet no puede contener productos de distinto formato.',
                    ]);
                }

                $formato = strtoupper((string) reset($formatosUnicos));
                $limite = $capacidades->get($formato);

                // No permitir pallets sin una capacidad configurada: antes, el
                // valor null omitía la validación y dejaba pasar cantidades libres.
                if ($limite === null || (int) $limite <= 0) {
                    throw ValidationException::withMessages([
                        'pallet' =>
                        "El formato {$formato} no tiene una capacidad válida configurada en WMS. Configura sus cajas por pallet antes de registrar el ingreso.",
                    ]);
                }

                if ($totalPallet > (int) $limite) {
                    throw ValidationException::withMessages([
                        'pallet' =>
                        "El pallet del formato {$formato} contiene {$totalPallet} cajas y supera su capacidad máxima de {$limite} cajas.",
                    ]);
                }

                $gruposValidados[] = [
                    'galpon' => $ubicacion->galpon->codigo,
                    'ubicacion' => $ubicacion->codigo,
                    'items' => $itemsValidados,
                ];
            }

            /*
         * 7. Las discrepancias se documentan y quedan pendientes de revisión.
         * No requieren autorización previa en esta etapa.
         */

            /*
         * 8. Generar correlativos después de validar todo.
         */
            $pallets = $this->palletService->generarSiguientes(
                $almacen,
                count($gruposValidados)
            );

            /*
         * 9. Persistir ingresos y trazabilidad.
         */
            $resumen = [];
            $discrepanciasRegistradas = [];

            foreach ($gruposValidados as $indiceGrupo => $grupo) {
                $codigoPallet = $pallets[$indiceGrupo];

                foreach ($grupo['items'] as $item) {
                    $ingreso = WmsIngreso::create([
                        'rdocum' => $idRecepcion,
                        'doc_origen' => $idRecepcion,
                        'rfecha' => $cabecera->fecha,
                        'tipo_ingreso' => 'recepcion',
                        'pallet' => $codigoPallet,
                        'codigo' => $item['codigo'],
                        'descrip' => $item['descrip'],
                        'descrip1' => $item['descrip1'],
                        'clote' => $item['clote'],
                        'cantidad' => $item['cantidad'],
                        'almacen' => $codigoAlmacen,
                        'galpon' => $grupo['galpon'],
                        'ubicacion' => $grupo['ubicacion'],
                    ]);

                    foreach ($item['asignaciones'] as $asignacion) {
                        WmsRecepcionDetalle::create([
                            'wms_ingreso_id' => $ingreso->id,
                            'id_detalle_origen' => $asignacion['id_detalle_origen'],
                            'cantidad_asignada' => $asignacion['cantidad'],
                        ]);

                        if ($asignacion['es_exceso']) {
                            WmsRecepcionExcepcion::create([
                                'wms_ingreso_id' => $ingreso->id,
                                'id_detalle_origen' => $asignacion['id_detalle_origen'],
                                'tipo' => 'exceso_cantidad',
                                'lote_origen' => $asignacion['lote_origen'],
                                'lote_fisico' => $item['clote'],

                                // Saldo disponible antes de esta asignación.
                                'cantidad_origen' => $asignacion['saldo_disponible_antes'],

                                // Cantidad física efectivamente ingresada.
                                // La diferencia representa el excedente.
                                'cantidad_fisica' => $asignacion['cantidad'],

                                'observacion' => $item['observacion_exceso'],
                                'autorizado_por' => null,
                                'autorizado_at' => null,
                                'estado_revision' => 'pendiente',
                            ]);
                            $discrepanciasRegistradas[] = [
                                'tipo' => 'exceso_cantidad',
                                'codigo' => $item['codigo'],
                                'lote_origen' => $asignacion['lote_origen'],
                                'lote_fisico' => $item['clote'],
                                'cantidad_origen' => $asignacion['saldo_disponible_antes'],
                                'cantidad_fisica' => $asignacion['cantidad'],
                                'estado_revision' => 'pendiente',
                                'wms_ingreso_id' => $ingreso->id,
                            ];
                        }

                        if ($asignacion['es_discrepancia']) {
                            WmsRecepcionExcepcion::create([
                                'wms_ingreso_id' => $ingreso->id,
                                'id_detalle_origen' => $asignacion['id_detalle_origen'],
                                'tipo' => 'discrepancia_lote',
                                'lote_origen' => $asignacion['lote_origen'],
                                'lote_fisico' => $item['clote'],
                                'cantidad_origen' => $asignacion['cantidad'],
                                'cantidad_fisica' => $asignacion['cantidad'],
                                'observacion' => $item['observacion_lote'],
                                'autorizado_por' => null,
                                'autorizado_at' => null,
                                'estado_revision' => 'pendiente',
                            ]);
                            $discrepanciasRegistradas[] = [
                                'tipo' => 'discrepancia_lote',
                                'codigo' => $item['codigo'],
                                'lote_origen' => $asignacion['lote_origen'],
                                'lote_fisico' => $item['clote'],
                                'cantidad_origen' => $asignacion['cantidad'],
                                'cantidad_fisica' => $asignacion['cantidad'],
                                'estado_revision' => 'pendiente',
                                'wms_ingreso_id' => $ingreso->id,
                            ];
                        }
                    }
                }

                $resumen[] = [
                    'pallet' => $codigoPallet,
                    'galpon' => $grupo['galpon'],
                    'ubicacion' => $grupo['ubicacion'],
                ];
            }

            return [
                'message' => 'Ingreso por recepción registrado correctamente.',
                'recepcion' => $idRecepcion,
                'pallets' => $resumen,
                'total_pallets' => count($resumen),
                'total_cajas' => array_sum(array_map(
                    fn($grupo) => array_sum(array_column($grupo['items'], 'cantidad')),
                    $gruposValidados
                )),
                'total_discrepancias' => count($discrepanciasRegistradas),
                'discrepancias' => $discrepanciasRegistradas,
            ];
        });
    }
}
