<?php

namespace Tests\\Unit\\Wms;

use App\\Models\\WmsOrdenTrabajo;
use App\\Models\\WmsReubicacion;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schema;
use Tests\\TestCase;

class WmsReubicacionOrdenTrabajoTest extends TestCase
{
    private string $previousDefaultConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousDefaultConnection = config('database.default');

        config([
            'database.connections.pt1_reubicacion_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        DB::purge('pt1_reubicacion_test');
        DB::setDefaultConnection('pt1_reubicacion_test');

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

        Schema::create('wms_reubicaciones', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('orden_trabajo_id')->nullable();
            $table->string('tipo');
            $table->string('codigo')->nullable();
            $table->string('clote')->nullable();
            $table->string('descrip')->nullable();
            $table->string('descrip1')->nullable();
            $table->unsignedInteger('cantidad')->default(1);
            $table->string('pallet_origen')->nullable();
            $table->string('almacen_origen')->nullable();
            $table->string('galpon_origen')->nullable();
            $table->string('ubicacion_origen')->nullable();
            $table->string('pallet_destino')->nullable();
            $table->string('almacen_destino')->nullable();
            $table->string('galpon_destino')->nullable();
            $table->string('ubicacion_destino')->nullable();
            $table->string('observacion')->nullable();
            $table->unsignedBigInteger('created_id')->nullable();
            $table->unsignedBigInteger('update_id')->nullable();
            $table->unsignedBigInteger('delete_id')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::disconnect('pt1_reubicacion_test');
        DB::purge('pt1_reubicacion_test');
        DB::setDefaultConnection($this->previousDefaultConnection);

        parent::tearDown();
    }

    public function test_it_persists_the_work_order_reference_on_a_relocation(): void
    {
        $orden = WmsOrdenTrabajo::query()->create([
            'tipo_registro' => '1',
            'id_registro' => 'OT-TEST-01',
            'estado' => 'pendiente',
        ]);

        $reubicacion = WmsReubicacion::query()->create([
            'orden_trabajo_id' => $orden->id,
            'tipo' => 'pallet_completo',
            'codigo' => 'COD-TEST',
            'cantidad' => 10,
        ]);

        $this->assertSame($orden->id, $reubicacion->fresh()->orden_trabajo_id);
        $this->assertSame($orden->id, $reubicacion->ordenTrabajo->id);
    }

    public function test_manual_relocations_can_have_no_work_order_reference(): void
    {
        $reubicacion = WmsReubicacion::query()->create([
            'tipo' => 'pallet_completo',
            'codigo' => 'COD-TEST',
            'cantidad' => 10,
        ]);

        $this->assertNull($reubicacion->fresh()->orden_trabajo_id);
        $this->assertNull($reubicacion->ordenTrabajo);
    }
}
