<?php

namespace Tests\Feature\Wms;

use App\Http\Controllers\Wms\WmsReubicacionController;
use App\Services\ReubicacionService;
use App\Services\SaldoService;
use App\Services\WmsStockLockService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class WmsReubicacionReservadaTest extends TestCase
{
    private string $previousDefaultConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousDefaultConnection = config('database.default');

        config([
            'database.connections.pt1_reubicacion_reservada_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        DB::purge('pt1_reubicacion_reservada_test');
        DB::setDefaultConnection('pt1_reubicacion_reservada_test');

        Schema::create('wms_reubicaciones', function (Blueprint $table): void {
            $table->id();
        });
    }

    protected function tearDown(): void
    {
        DB::disconnect('pt1_reubicacion_reservada_test');
        DB::purge('pt1_reubicacion_reservada_test');
        DB::setDefaultConnection($this->previousDefaultConnection);

        parent::tearDown();
    }

    public function test_non_supervisor_cannot_relocate_a_pallet_reserved_by_a_pending_work_order(): void
    {
        $lockService = Mockery::mock(WmsStockLockService::class);
        $lockService->shouldReceive('bloquearPallets')->once()->with(['PALLET-01']);
        $lockService->shouldReceive('cantidadReservada')->once()->with('PALLET-01')->andReturn(12);

        $relocationService = Mockery::mock(ReubicacionService::class);
        $relocationService->shouldNotReceive('trasladarPalletCompleto');

        $response = $this->controller($lockService, $relocationService)
            ->storePalletCompleto($this->request([
                'pallet_origen' => 'PALLET-01',
                'galpon_destino' => 'PLAYA',
                'ubicacion_destino' => '1',
            ], false));

        $this->assertSame(409, $response->getStatusCode());
        $this->assertTrue($response->getData(true)['requires_supervisor_confirmation']);
        $this->assertFalse($response->getData(true)['supervisor_can_confirm']);
        $this->assertSame(0, DB::table('wms_reubicaciones')->count());
    }

    public function test_supervisor_must_explicitly_confirm_relocation_of_a_reserved_pallet(): void
    {
        $lockService = Mockery::mock(WmsStockLockService::class);
        $lockService->shouldReceive('bloquearPallets')->once()->with(['PALLET-01']);
        $lockService->shouldReceive('cantidadReservada')->once()->with('PALLET-01')->andReturn(12);

        $relocationService = Mockery::mock(ReubicacionService::class);
        $relocationService->shouldNotReceive('trasladarPalletCompleto');

        $response = $this->controller($lockService, $relocationService)
            ->storePalletCompleto($this->request([
                'pallet_origen' => 'PALLET-01',
                'galpon_destino' => 'PLAYA',
                'ubicacion_destino' => '1',
            ], true));

        $this->assertSame(409, $response->getStatusCode());
        $this->assertTrue($response->getData(true)['requires_supervisor_confirmation']);
        $this->assertTrue($response->getData(true)['supervisor_can_confirm']);
        $this->assertSame(0, DB::table('wms_reubicaciones')->count());
    }

    public function test_supervisor_can_relocate_a_reserved_pallet_after_explicit_confirmation(): void
    {
        $lockService = Mockery::mock(WmsStockLockService::class);
        $lockService->shouldReceive('bloquearPallets')->once()->with(['PALLET-01']);
        $lockService->shouldReceive('cantidadReservada')->once()->with('PALLET-01')->andReturn(12);

        $saldoService = Mockery::mock(SaldoService::class);
        $saldoService->shouldReceive('calcular')->once()->with(['pallet' => 'PALLET-01'])->andReturn(new Collection([
            [
                'pallet' => 'PALLET-01',
                'codigo' => 'COD-01',
                'clote' => 'LOTE-01',
                'almacen' => '110',
                'galpon' => 'A',
                'ubicacion' => '01',
                'saldo' => 20,
            ],
        ]));

        $relocationService = Mockery::mock(ReubicacionService::class);
        $relocationService->shouldReceive('trasladarPalletCompleto')
            ->once()
            ->with('PALLET-01', 'PLAYA', '1', null, null)
            ->andReturn(1);

        $controller = new WmsReubicacionController($saldoService, $relocationService, $lockService);
        $response = $controller->storePalletCompleto($this->request([
            'pallet_origen' => 'PALLET-01',
            'galpon_destino' => 'PLAYA',
            'ubicacion_destino' => '1',
            'confirmar_reubicacion_reservada' => '1',
        ], true));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Pallet reubicado correctamente.', $response->getData(true)['message']);
    }

    private function controller(WmsStockLockService $lockService, ReubicacionService $relocationService): WmsReubicacionController
    {
        return new WmsReubicacionController(
            Mockery::mock(SaldoService::class),
            $relocationService,
            $lockService
        );
    }

    private function request(array $data, bool $isSupervisor): Request
    {
        $request = Request::create('/wms/reubicacion/pallet-completo', 'POST', $data);
        $user = new class($isSupervisor) {
            public function __construct(private bool $isSupervisor) {}

            public function hasAnyRole(array $roles): bool
            {
                return $this->isSupervisor && in_array('WMS-ADMIN', $roles, true);
            }
        };

        $request->setUserResolver(fn () => $user);

        return $request;
    }
}
