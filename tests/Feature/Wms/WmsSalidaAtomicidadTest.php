<?php

namespace Tests\Feature\Wms;

use App\Http\Controllers\Wms\WmsSalidaController;
use App\Models\WmsExcepcionDespacho;
use App\Models\WmsOrdenTrabajo;
use App\Services\LoteExcepcionService;
use App\Services\SaldoService;
use App\Services\WmsStockLockService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class WmsSalidaAtomicidadTest extends TestCase
{
    private string $previousDefaultConnection;
    private array $previousFaboceConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousDefaultConnection = config('database.default');

        config(['database.connections.wms_salida_atomicity_test' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);
        $this->previousFaboceConnection = config('database.connections.faboce2026');

        DB::purge('wms_salida_atomicity_test');
        DB::setDefaultConnection('wms_salida_atomicity_test');

        // Aislar la base externa del ERP para que la prueba no dependa
        // de credenciales ni de datos reales.
        config([
            'database.connections.faboce2026' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        DB::purge('faboce2026');

        Schema::connection('faboce2026')->create(
            'log_registro',
            function (Blueprint $table): void {
                $table->string('id')->primary();
                $table->date('fecha')->nullable();
            }
        );

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
        });

        Schema::create('wms_salidas', function (Blueprint $table): void {
            $table->id();
            $table->string('tipo_registro')->nullable();
            $table->string('id_registro')->nullable();
            $table->string('glosa')->nullable();
            $table->string('pallet')->nullable();
            $table->string('codigo')->nullable();
            $table->string('clote')->nullable();
            $table->string('lote_declarado')->nullable();
            $table->boolean('es_excepcion_lote')->default(false);
            $table->string('descrip')->nullable();
            $table->string('descrip1')->nullable();
            $table->unsignedInteger('cantidad')->default(0);
            $table->string('almacen')->nullable();
            $table->string('galpon')->nullable();
            $table->string('ubicacion')->nullable();
            $table->unsignedBigInteger('created_id')->nullable();
            $table->unsignedBigInteger('update_id')->nullable();
            $table->unsignedBigInteger('delete_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('wms_ordenes_trabajo', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('created_id')->nullable();
            $table->unsignedBigInteger('update_id')->nullable();
            $table->unsignedBigInteger('delete_id')->nullable();
            $table->string('tipo_registro');
            $table->string('id_registro');
            $table->string('glosa')->nullable();
            $table->string('estado')->default('pendiente');
            $table->timestamp('completada_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('wms_orden_trabajo_detalle', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('orden_trabajo_id');
            $table->string('pallet');
            $table->string('codigo');
            $table->string('clote')->nullable();
            $table->string('lote_declarado')->nullable();
            $table->boolean('es_excepcion_lote')->default(false);
            $table->string('descrip')->nullable();
            $table->string('descrip1')->nullable();
            $table->unsignedInteger('cantidad');
            $table->string('almacen_origen');
            $table->string('galpon_origen');
            $table->string('ubicacion_origen');
            $table->boolean('chequeado')->default(false);
            $table->unsignedBigInteger('chequeado_por')->nullable();
            $table->timestamp('chequeado_at')->nullable();
            $table->timestamps();
        });

        Schema::create('wms_excepciones_despacho', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('created_id')->nullable();
            $table->unsignedBigInteger('update_id')->nullable();
            $table->unsignedBigInteger('delete_id')->nullable();
            $table->string('tipo_registro', 5);
            $table->string('id_registro', 20);
            $table->string('codigo', 30);
            $table->string('descrip', 60)->nullable();
            $table->string('descrip1', 60)->nullable();
            $table->string('lote_solicitado', 30)->nullable();
            $table->string('lote_aplicado', 30);
            $table->unsignedInteger('cantidad');
            $table->string('motivo', 200)->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::disconnect('faboce2026');
        DB::purge('faboce2026');

        config([
            'database.connections.faboce2026' => $this->previousFaboceConnection,
        ]);

        DB::disconnect('wms_salida_atomicity_test');
        DB::purge('wms_salida_atomicity_test');

        DB::setDefaultConnection($this->previousDefaultConnection);

        parent::tearDown();
    }

    public function test_exception_failure_rolls_back_order_details_and_exception_records(): void
    {
        $locks = \Mockery::mock(WmsStockLockService::class);
        $locks->shouldReceive('bloquearNota')->once();
        $locks->shouldReceive('bloquearPallets')->once()->with(['PALLET-ATOMIC-01']);

        $saldo = \Mockery::mock(SaldoService::class);
        $saldo->shouldReceive('saldoDeGrupo')
            ->twice()
            ->andReturn(10);

        $saldo->shouldReceive('reservadoEnOtsPendientes')
            ->once()
            ->andReturn(0);

        $lotes = \Mockery::mock(LoteExcepcionService::class);
        $lotes->shouldReceive('esElegibleParaCambioLote')->once()->andReturn(true);
        $lotes->shouldReceive('validar')->once()->andReturn([
            'valido' => true,
            'mensaje' => 'Stock disponible',
        ]);
        $lotes->shouldReceive('registrarExcepcion')
            ->once()
            ->andThrow(new RuntimeException('Fallo simulado al registrar excepción'));

        $controller = new WmsSalidaController($saldo, $lotes, $locks);

        $request = Request::create('/wms/salidas', 'POST', [
            'tipo_registro' => '1',
            'id_registro' => 'ATOMIC-TEST-01',
            'lines' => [[
                'codigo' => 'COD-ATOMIC',
                'descrip' => 'Producto de prueba',
                'lote_declarado' => '240101-00001',
                'clote' => '230101-00001',
                'cantidad_despacho' => 2,
                'salidas' => [[
                    'pallet' => 'PALLET-ATOMIC-01',
                    'almacen' => '110',
                    'galpon' => 'A',
                    'ubicacion' => '01',
                    'cantidad' => 2,
                ]],
            ]],
        ]);

        try {
            $controller->store($request);
            $this->fail('Se esperaba el fallo simulado al registrar la excepción.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Fallo simulado al registrar excepción',
                $exception->getMessage()
            );
        }

        $this->assertSame(0, WmsOrdenTrabajo::query()->count());
        $this->assertSame(0, DB::table('wms_orden_trabajo_detalle')->count());
        $this->assertSame(0, WmsExcepcionDespacho::query()->count());
        $this->assertSame(0, DB::transactionLevel());
    }
}
