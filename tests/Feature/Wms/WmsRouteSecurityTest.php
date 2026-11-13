<?php

namespace Tests\Feature\Wms;

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class WmsRouteSecurityTest extends TestCase
{
    /**
     * WMS routes must remain behind authentication and their operation permission.
     *
     * These tests inspect the registered route middleware without requiring
     * warehouse data or a live database.
     */
    public function test_work_order_routes_require_authentication_and_permission(): void
    {
        $this->assertRouteHasMiddleware('wms.ordenes.trabajo.index', 'auth');
        $this->assertRouteHasMiddleware('wms.ordenes.trabajo.index', 'permission:wms.ordenes.trabajo');

        $this->assertRouteHasMiddleware('wms.ordenes.trabajo.marcar-pallet', 'auth');
        $this->assertRouteHasMiddleware('wms.ordenes.trabajo.marcar-pallet', 'permission:wms.ordenes.trabajo');
    }

    public function test_inventory_and_configuration_routes_keep_operation_permissions(): void
    {
        $this->assertRouteHasMiddleware('wms.inventario.index', 'auth');
        $this->assertRouteHasMiddleware('wms.inventario.index', 'permission:wms.inventario');

        $this->assertRouteHasMiddleware('wms.configurar.index', 'auth');
        $this->assertRouteHasMiddleware('wms.configurar.index', 'permission:wms.configurar');
    }

    public function test_rrhh_agency_routes_require_an_authorized_role(): void
    {
        $this->assertRouteHasMiddleware('rrhh.agencias.index', 'auth');
        $this->assertRouteHasMiddleware('rrhh.agencias.index', 'role:RRHH-ADMIN|SIS-ADMIN');
        $this->assertRouteHasMiddleware('rrhh.agencias.asignaciones.guardar', 'auth');
        $this->assertRouteHasMiddleware('rrhh.agencias.asignaciones.guardar', 'role:RRHH-ADMIN|SIS-ADMIN');
    }

    public function test_license_assignment_requires_system_administrator_role(): void
    {
        $this->assertRouteHasMiddleware('usuarios.license', 'auth');
        $this->assertRouteHasMiddleware('usuarios.license', 'role:SIS-ADMIN');
    }

    private function assertRouteHasMiddleware(string $routeName, string $expectedMiddleware): void
    {
        $route = Route::getRoutes()->getByName($routeName);

        $this->assertInstanceOf(
            RoutingRoute::class,
            $route,
            sprintf('Expected route [%s] to be registered.', $routeName)
        );

        $this->assertContains(
            $expectedMiddleware,
            $route->gatherMiddleware(),
            sprintf('Expected route [%s] to include middleware [%s].', $routeName, $expectedMiddleware)
        );
    }
}
