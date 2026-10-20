<?php

namespace Tests\Unit\Wms;

use App\Services\SaldoService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SaldoServiceReservationTest extends TestCase
{
    private string $previousDefaultConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousDefaultConnection = config('database.default');

        config([
            'database.connections.pt1_reservations_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        DB::purge('pt1_reservations_test');
        DB::setDefaultConnection('pt1_reservations_test');

        Schema::create('wms_ordenes_trabajo', function (Blueprint $table): void {
            $table->id();
            $table->string('estado');
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::create('wms_orden_trabajo_detalle', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('orden_trabajo_id');
            $table->string('codigo');
            $table->string('clote')->nullable();
            $table->string('pallet');
            $table->string('almacen_origen');
            $table->string('galpon_origen');
            $table->string('ubicacion_origen');
            $table->unsignedInteger('cantidad');
        });
    }

    protected function tearDown(): void
    {
        DB::disconnect('pt1_reservations_test');
        DB::purge('pt1_reservations_test');
        DB::setDefaultConnection($this->previousDefaultConnection);

        parent::tearDown();
    }

    public function test_it_sums_pending_reservations_for_the_exact_physical_stock_group(): void
    {
        DB::table('wms_ordenes_trabajo')->insert([
            ['id' => 1, 'estado' => 'pendiente', 'deleted_at' => null],
            ['id' => 2, 'estado' => 'completada', 'deleted_at' => null],
            ['id' => 3, 'estado' => 'pendiente', 'deleted_at' => now()],
        ]);

        DB::table('wms_orden_trabajo_detalle')->insert([
            [
                'orden_trabajo_id' => 1, 'codigo' => 'COD-01', 'clote' => 'LOTE-A',
                'pallet' => 'P-01', 'almacen_origen' => '110', 'galpon_origen' => 'NORTE',
                'ubicacion_origen' => 'A1', 'cantidad' => 12,
            ],
            [
                'orden_trabajo_id' => 1, 'codigo' => 'COD-01', 'clote' => 'LOTE-A',
                'pallet' => 'P-01', 'almacen_origen' => '110', 'galpon_origen' => 'NORTE',
                'ubicacion_origen' => 'A1', 'cantidad' => 8,
            ],
            [
                'orden_trabajo_id' => 2, 'codigo' => 'COD-01', 'clote' => 'LOTE-A',
                'pallet' => 'P-01', 'almacen_origen' => '110', 'galpon_origen' => 'NORTE',
                'ubicacion_origen' => 'A1', 'cantidad' => 30,
            ],
            [
                'orden_trabajo_id' => 3, 'codigo' => 'COD-01', 'clote' => 'LOTE-A',
                'pallet' => 'P-01', 'almacen_origen' => '110', 'galpon_origen' => 'NORTE',
                'ubicacion_origen' => 'A1', 'cantidad' => 40,
            ],
            [
                'orden_trabajo_id' => 1, 'codigo' => 'COD-02', 'clote' => 'LOTE-A',
                'pallet' => 'P-01', 'almacen_origen' => '110', 'galpon_origen' => 'NORTE',
                'ubicacion_origen' => 'A1', 'cantidad' => 50,
            ],
            [
                'orden_trabajo_id' => 1, 'codigo' => 'COD-01', 'clote' => 'LOTE-B',
                'pallet' => 'P-01', 'almacen_origen' => '110', 'galpon_origen' => 'NORTE',
                'ubicacion_origen' => 'A1', 'cantidad' => 60,
            ],
            [
                'orden_trabajo_id' => 1, 'codigo' => 'COD-01', 'clote' => 'LOTE-A',
                'pallet' => 'P-02', 'almacen_origen' => '110', 'galpon_origen' => 'NORTE',
                'ubicacion_origen' => 'A1', 'cantidad' => 70,
            ],
            [
                'orden_trabajo_id' => 1, 'codigo' => 'COD-01', 'clote' => 'LOTE-A',
                'pallet' => 'P-01', 'almacen_origen' => '113', 'galpon_origen' => 'NORTE',
                'ubicacion_origen' => 'A1', 'cantidad' => 80,
            ],
            [
                'orden_trabajo_id' => 1, 'codigo' => 'COD-01', 'clote' => 'LOTE-A',
                'pallet' => 'P-01', 'almacen_origen' => '110', 'galpon_origen' => 'SUR',
                'ubicacion_origen' => 'A1', 'cantidad' => 90,
            ],
            [
                'orden_trabajo_id' => 1, 'codigo' => 'COD-01', 'clote' => 'LOTE-A',
                'pallet' => 'P-01', 'almacen_origen' => '110', 'galpon_origen' => 'NORTE',
                'ubicacion_origen' => 'A2', 'cantidad' => 100,
            ],
        ]);

        $service = app(SaldoService::class);

        $this->assertSame(
            20,
            $service->reservadoEnOtsPendientes('COD-01', 'LOTE-A', 'P-01', '110', 'norte', 'a1')
        );
    }

    public function test_it_returns_zero_when_the_physical_group_has_no_pending_reservations(): void
    {
        DB::table('wms_ordenes_trabajo')->insert([
            ['id' => 1, 'estado' => 'completada', 'deleted_at' => null],
        ]);

        DB::table('wms_orden_trabajo_detalle')->insert([
            [
                'orden_trabajo_id' => 1, 'codigo' => 'COD-01', 'clote' => 'LOTE-A',
                'pallet' => 'P-01', 'almacen_origen' => '110', 'galpon_origen' => 'NORTE',
                'ubicacion_origen' => 'A1', 'cantidad' => 10,
            ],
        ]);

        $service = app(SaldoService::class);

        $this->assertSame(
            0,
            $service->reservadoEnOtsPendientes('COD-01', 'LOTE-A', 'P-01', '110', 'NORTE', 'A1')
        );
    }
}
