<?php

namespace Tests\\Unit;

use App\\Exceptions\\Wms\\SaldoInsuficienteException;
use App\\Services\\SaldoService;
use Mockery;
use Tests\\TestCase;

class SaldoServiceDisponibleTest extends TestCase
{
    public function test_calcula_disponible_descontando_reservas_de_ot_pendientes(): void
    {
        $service = Mockery::mock(SaldoService::class)->makePartial();
        $service->shouldReceive('saldoDeGrupo')->once()->andReturn(12);
        $service->shouldReceive('reservadoEnOtsPendientes')->once()->andReturn(5);

        $disponible = $service->validarDisponible('PROD-1', 'L1', 'P1', '110', 'PLAYA', '1', 7);

        $this->assertSame(7, $disponible);
    }

    public function test_rechaza_solicitud_superior_al_saldo_disponible(): void
    {
        $service = Mockery::mock(SaldoService::class)->makePartial();
        $service->shouldReceive('saldoDeGrupo')->once()->andReturn(12);
        $service->shouldReceive('reservadoEnOtsPendientes')->once()->andReturn(5);

        try {
            $service->validarDisponible('PROD-1', 'L1', 'P1', '110', 'PLAYA', '1', 8);
            $this->fail('Se esperaba una excepción por saldo insuficiente.');
        } catch (SaldoInsuficienteException $exception) {
            $this->assertSame(7, $exception->disponible);
            $this->assertSame('no tiene saldo suficiente. Disponible: 7', $exception->getMessage());
        }
    }

    public function test_rechaza_cantidad_solicitada_menor_a_uno(): void
    {
        $service = Mockery::mock(SaldoService::class)->makePartial();
        $service->shouldReceive('saldoDeGrupo')->once()->andReturn(12);
        $service->shouldReceive('reservadoEnOtsPendientes')->once()->andReturn(5);

        $this->expectException(SaldoInsuficienteException::class);

        $service->validarDisponible('PROD-1', 'L1', 'P1', '110', 'PLAYA', '1', 0);
    }
}
