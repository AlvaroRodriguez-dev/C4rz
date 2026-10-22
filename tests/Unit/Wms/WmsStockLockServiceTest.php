<?php

namespace Tests\Unit\Wms;

use App\Services\WmsStockLockService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WmsStockLockServiceTest extends TestCase
{
    private string $previousDefaultConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousDefaultConnection = config('database.default');

        config([
            'database.connections.pt1_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        DB::purge('pt1_test');
        DB::setDefaultConnection('pt1_test');

        Schema::create('wms_ingresos', function (Blueprint $table): void {
            $table->id();
            $table->string('pallet');
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::create('wms_ordenes_trabajo', function (Blueprint $table): void {
            $table->id();
            $table->string('estado');
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::create('wms_orden_trabajo_detalle', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('orden_trabajo_id');
            $table->string('pallet');
            $table->unsignedInteger('cantidad');
        });
    }

    protected function tearDown(): void
    {
        DB::disconnect('pt1_test');
        DB::purge('pt1_test');
        DB::setDefaultConnection($this->previousDefaultConnection);

        parent::tearDown();
    }

    public function test_it_sums_reservations_only_for_pending_non_deleted_work_orders(): void
    {
        DB::table('wms_ordenes_trabajo')->insert([
            ['id' => 1, 'estado' => 'pendiente', 'deleted_at' => null],
            ['id' => 2, 'estado' => 'completada', 'deleted_at' => null],
            ['id' => 3, 'estado' => 'pendiente', 'deleted_at' => now()],
        ]);

        DB::table('wms_orden_trabajo_detalle')->insert([
            ['orden_trabajo_id' => 1, 'pallet' => 'PALLET-01', 'cantidad' => 12],
            ['orden_trabajo_id' => 1, 'pallet' => 'PALLET-01', 'cantidad' => 8],
            ['orden_trabajo_id' => 2, 'pallet' => 'PALLET-01', 'cantidad' => 30],
            ['orden_trabajo_id' => 3, 'pallet' => 'PALLET-01', 'cantidad' => 40],
            ['orden_trabajo_id' => 1, 'pallet' => 'PALLET-02', 'cantidad' => 7],
        ]);

        $service = app(WmsStockLockService::class);

        $this->assertSame(20, $service->cantidadReservada('PALLET-01'));
    }

    public function test_it_returns_zero_when_a_pallet_has_no_pending_reservation(): void
    {
        DB::table('wms_ordenes_trabajo')->insert([
            ['id' => 1, 'estado' => 'completada', 'deleted_at' => null],
        ]);

        DB::table('wms_orden_trabajo_detalle')->insert([
            ['orden_trabajo_id' => 1, 'pallet' => 'PALLET-01', 'cantidad' => 15],
        ]);

        $service = app(WmsStockLockService::class);

        $this->assertSame(0, $service->cantidadReservada('PALLET-01'));
        $this->assertSame(0, $service->cantidadReservada('PALLET-INEXISTENTE'));
    }

    public function test_it_rejects_locking_stock_outside_a_transaction(): void
    {
        $service = app(WmsStockLockService::class);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('debe ejecutarse dentro de una transacción');

        $service->bloquearPallets(['PALLET-01']);
    }

    public function test_it_can_lock_existing_pallet_rows_and_ignore_empty_or_unknown_pallets(): void
    {
        DB::table('wms_ingresos')->insert([
            ['id' => 1, 'pallet' => 'PALLET-02', 'deleted_at' => null],
            ['id' => 2, 'pallet' => 'PALLET-01', 'deleted_at' => null],
        ]);

        $service = app(WmsStockLockService::class);

        DB::transaction(function () use ($service): void {
            $service->bloquearPallets(['PALLET-02', 'PALLET-01', 'PALLET-02']);
            $service->bloquearPallets([]);
            $service->bloquearPallets(['PALLET-NO-EXISTE']);
        });

        $this->assertSame(2, DB::table('wms_ingresos')->count());
    }
}
