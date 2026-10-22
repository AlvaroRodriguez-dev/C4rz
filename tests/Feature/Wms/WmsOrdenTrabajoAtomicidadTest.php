<?php

namespace Tests\Feature\Wms;

use App\Http\Controllers\Wms\WmsOrdenTrabajoController;
use App\Models\WmsOrdenTrabajo;
use App\Services\ReubicacionService;
use App\Services\WmsStockLockService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class WmsOrdenTrabajoAtomicidadTest extends TestCase
{
    private string $previousDefaultConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousDefaultConnection = config('database.default');
        config(['database.connections.wms_ot_atomicity_test' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);

        DB::purge('wms_ot_atomicity_test');
        DB::setDefaultConnection('wms_ot_atomicity_test');

        Schema::create('wms_ordenes_trabajo', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('created_id')->nullable();
            $table->unsignedBigInteger('update_id')->nullable();
            $table->unsignedBigInteger('delete_id')->nullable();
            $table->string('tipo_registro')->nullable();
            $table->string('id_registro')->nullable();
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
            $table->string('codigo')->nullable();
            $table->string('clote')->nullable();
            $table->string('lote_declarado')->nullable();
            $table->boolean('es_excepcion_lote')->default(false);
            $table->string('descrip')->nullable();
            $table->string('descrip1')->nullable();
            $table->unsignedInteger('cantidad')->default(1);
            $table->string('almacen_origen')->nullable();
            $table->string('galpon_origen')->nullable();
            $table->string('ubicacion_origen')->nullable();
            $table->boolean('chequeado')->default(false);
            $table->unsignedBigInteger('chequeado_por')->nullable();
            $table->timestamp('chequeado_at')->nullable();
            $table->timestamps();
        });
        
        Schema::create('wms_salidas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('created_id')->nullable();
            $table->unsignedBigInteger('update_id')->nullable();
            $table->unsignedBigInteger('delete_id')->nullable();
            $table->string('tipo_registro')->nullable();
            $table->string('id_registro')->nullable();
            $table->string('glosa')->nullable();
            $table->string('pallet');
            $table->string('codigo')->nullable();
            $table->string('clote')->nullable();
            $table->string('lote_declarado')->nullable();
            $table->boolean('es_excepcion_lote')->default(false);
            $table->string('descrip')->nullable();
            $table->string('descrip1')->nullable();
            $table->unsignedInteger('cantidad')->default(1);
            $table->string('almacen')->nullable();
            $table->string('galpon')->nullable();
            $table->string('ubicacion')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::disconnect('wms_ot_atomicity_test');
        DB::purge('wms_ot_atomicity_test');
        DB::setDefaultConnection($this->previousDefaultConnection);

        parent::tearDown();
    }

    public function test_failed_finalization_rolls_back_the_check_that_triggered_it(): void
    {
        $orden = WmsOrdenTrabajo::query()->create([
            'tipo_registro' => '1',
            'id_registro' => 'OT-ATOMIC-01',
            'estado' => 'pendiente',
        ]);

        $detalle = $orden->detalles()->create([
            'pallet' => 'PALLET-01',
            'codigo' => 'COD-01',
            'clote' => 'LOTE-01',
            'cantidad' => 5,
            'almacen_origen' => '110',
            'galpon_origen' => 'A',
            'ubicacion_origen' => '01',
            'chequeado' => false,
        ]);

        $locks = \Mockery::mock(WmsStockLockService::class);
        $locks->shouldReceive('bloquearPallets')->once()->with(['PALLET-01']);

        $relocations = \Mockery::mock(ReubicacionService::class);
        $relocations->shouldReceive('trasladarPalletCompleto')
            ->once()
            ->andThrow(new RuntimeException('Fallo simulado al reubicar'));

        $controller = new WmsOrdenTrabajoController($relocations, $locks);

        try {
            $controller->marcarPallet(
                Request::create('/wms/ordenes-trabajo/1/pallet/PALLET-01', 'POST'),
                $orden,
                'PALLET-01'
            );
            $this->fail('Se esperaba que el fallo de finalización se propagara.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Fallo simulado al reubicar', $exception->getMessage());
        }

        $this->assertFalse((bool) $detalle->fresh()->chequeado);
        $this->assertNull($detalle->fresh()->chequeado_at);
        $this->assertSame('pendiente', $orden->fresh()->estado);
    }
    
    public function test_completed_work_order_cannot_register_the_same_exit_twice(): void
    {
        $orden = WmsOrdenTrabajo::query()->create([
            'tipo_registro' => '1',
            'id_registro' => 'OT-ATOMIC-02',
            'glosa' => 'Prueba de idempotencia',
            'estado' => 'pendiente',
        ]);

        $orden->detalles()->create([
            'pallet' => 'PALLET-02',
            'codigo' => 'COD-02',
            'clote' => 'LOTE-02',
            'lote_declarado' => 'LOTE-02',
            'cantidad' => 4,
            'almacen_origen' => '110',
            'galpon_origen' => 'A',
            'ubicacion_origen' => '02',
            'chequeado' => false,
        ]);

        $locks = \\Mockery::mock(WmsStockLockService::class);
        $locks->shouldReceive('bloquearPallets')->once()->with(['PALLET-02']);

        $relocations = \\Mockery::mock(ReubicacionService::class);
        $relocations->shouldReceive('trasladarPalletCompleto')
            ->once()
            ->with(
                'PALLET-02',
                'PLAYA',
                '1',
                null,
                "Traslado a zona de despacho - OT #{$orden->id}",
                $orden->id,
                \\Mockery::type(\\Carbon\\Carbon::class)
            )
            ->andReturn(1);

        $controller = new WmsOrdenTrabajoController($relocations, $locks);
        $firstResponse = $controller->marcarPallet(
            Request::create('/wms/ordenes-trabajo/2/pallet/PALLET-02', 'POST'),
            $orden,
            'PALLET-02'
        );

        $this->assertSame(200, $firstResponse->getStatusCode());
        $this->assertSame('completada', $orden->fresh()->estado);
        $this->assertSame(1, DB::table('wms_salidas')->count());

        $secondResponse = $controller->marcarPallet(
            Request::create('/wms/ordenes-trabajo/2/pallet/PALLET-02', 'POST'),
            $orden->fresh(),
            'PALLET-02'
        );

        $this->assertSame(422, $secondResponse->getStatusCode());
        $this->assertSame(1, DB::table('wms_salidas')->count());
    }


}
