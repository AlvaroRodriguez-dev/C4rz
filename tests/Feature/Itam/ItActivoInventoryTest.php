<?php

namespace Tests\Feature\Itam;

use App\Models\ItActivo;
use App\Models\ItTipoActivo;
use App\Models\ItUbicacion;
use App\Models\User;
use App\Services\Itam\ItActivoService;
use Database\Seeders\ItamCatalogSeeder;
use Database\Seeders\ItamInventoryCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ItActivoInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ItamCatalogSeeder::class);
        $this->seed(ItamInventoryCatalogSeeder::class);
    }

    public function test_it_registers_an_initial_asset_with_server_generated_code(): void
    {
        $activo = app(ItActivoService::class)->registrar([
            'tipo_activo_id' => ItTipoActivo::where('codigo', 'LAPTOP')->value('id'),
            'ubicacion_id' => ItUbicacion::where('codigo', 'STOCK-TI')->value('id'),
            'numero_serie' => 'FABOCE-TEST-001',
            'origen_documento' => 'INVENTARIO_INICIAL',
        ]);

        $this->assertStringStartsWith('IT-' . now()->format('Y') . '-', $activo->codigo_ti);
        $this->assertSame('DISPONIBLE', $activo->estado->codigo);
        $this->assertNull($activo->custodio_id);
        $this->assertDatabaseHas('it_activos', [
            'id' => $activo->id,
            'origen_documento' => 'INVENTARIO_INICIAL',
            'numero_serie' => 'FABOCE-TEST-001',
        ]);
    }

    public function test_it_identifies_one_unit_from_an_acquisition_receipt_atomically(): void
    {
        $user = User::factory()->create();

        $recepcionId = DB::table('it_recepciones')->insertGetId([
            'numero' => 'REC-TEST-001',
            'fecha' => now()->toDateString(),
            'recibido_por' => $user->id,
            'estado' => 'PENDIENTE_IDENTIFICACION',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $detalleId = DB::table('it_recepcion_detalles')->insertGetId([
            'recepcion_id' => $recepcionId,
            'descripcion' => 'Laptop de prueba',
            'cantidad_recibida' => 2,
            'cantidad_identificada' => 0,
            'cantidad_pendiente' => 2,
            'costo_unitario' => 4500,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $activo = app(ItActivoService::class)->registrar([
            'tipo_activo_id' => ItTipoActivo::where('codigo', 'LAPTOP')->value('id'),
            'ubicacion_id' => ItUbicacion::where('codigo', 'STOCK-TI')->value('id'),
            'numero_serie' => 'FABOCE-TEST-002',
            'origen_documento' => 'ADQUISICION',
            'recepcion_detalle_id' => $detalleId,
        ]);

        $this->assertSame($detalleId, $activo->recepcion_detalle_id);
        $this->assertDatabaseHas('it_recepcion_detalles', [
            'id' => $detalleId,
            'cantidad_identificada' => 1,
            'cantidad_pendiente' => 1,
        ]);
        $this->assertEquals('4500.00', $activo->costo);
    }

    public function test_it_rejects_acquisition_when_receipt_has_no_pending_units(): void
    {
        $user = User::factory()->create();

        $recepcionId = DB::table('it_recepciones')->insertGetId([
            'numero' => 'REC-TEST-002',
            'fecha' => now()->toDateString(),
            'recibido_por' => $user->id,
            'estado' => 'IDENTIFICADA',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $detalleId = DB::table('it_recepcion_detalles')->insertGetId([
            'recepcion_id' => $recepcionId,
            'descripcion' => 'Laptop sin pendientes',
            'cantidad_recibida' => 1,
            'cantidad_identificada' => 1,
            'cantidad_pendiente' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            app(ItActivoService::class)->registrar([
                'tipo_activo_id' => ItTipoActivo::where('codigo', 'LAPTOP')->value('id'),
                'ubicacion_id' => ItUbicacion::where('codigo', 'STOCK-TI')->value('id'),
                'numero_serie' => 'FABOCE-TEST-003',
                'origen_documento' => 'ADQUISICION',
                'recepcion_detalle_id' => $detalleId,
            ]);
            $this->fail('Se esperaba una ValidationException.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('recepcion_detalle_id', $exception->errors());
        }

        $this->assertDatabaseMissing('it_activos', ['numero_serie' => 'FABOCE-TEST-003']);
    }
}
