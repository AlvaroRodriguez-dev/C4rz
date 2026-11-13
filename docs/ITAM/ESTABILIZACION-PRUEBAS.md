# ITAM — estabilización de solicitudes y evaluaciones

## Alcance aprobado

- Inventario de esta fase: activos tecnológicos.
- No se habilitan como requerimientos independientes componentes, repuestos, accesorios ni servicios.
- Segunda evaluación: revisión de la evaluación inmediatamente anterior.
- Cada revisión se registra como una nueva fila; no se sobrescribe ni elimina la evaluación previa.

## Cambios implementados en esta rama

1. La solicitud valida el trabajador en `pgsql_rrhh.rrhh_personal` y exige `DELETED_AT IS NULL`.
2. El área se obtiene de `AREA_ID` en RRHH; se ignora cualquier área enviada por el navegador.
3. Si RRHH no responde, la solicitud no se registra.
4. El correlativo `SOL-TI-AAAA-NNNNNN` usa una fila contadora bloqueada dentro de la transacción.
5. La migración inicializa el contador desde el mayor correlativo ya existente por año.
6. La evaluación bloquea la solicitud, comprueba su estado después del bloqueo y vincula la nueva revisión con la evaluación anterior.
7. El catálogo se limita a equipos tecnológicos y el seeder evita reactivar elementos eliminados o reescribir fechas de creación.
8. Las rutas ITAM quedan registradas únicamente desde `bootstrap/app.php`.

## Pruebas de aceptación pendientes de ejecutar en entorno local

| ID | Prueba | Resultado esperado |
|---|---|---|
| ITAM-01 | Registrar solicitud con trabajador activo de RRHH | Se registra y toma el área real de RRHH |
| ITAM-02 | Enviar un `area_id` manipulado | Se ignora; se almacena el área de RRHH |
| ITAM-03 | Registrar con trabajador inexistente | Se rechaza sin crear solicitud |
| ITAM-04 | Registrar con trabajador eliminado/inactivo | Se rechaza sin crear solicitud |
| ITAM-05 | Simular RRHH no disponible | Se informa el problema y no se registra |
| ITAM-06 | Enviar tipo de requerimiento COMPONENTE, REPUESTO, ACCESORIO o SERVICIO | Se rechaza en servidor |
| ITAM-07 | Enviar tipo de activo inactivo/eliminado | Se rechaza en servidor |
| ITAM-08 | Crear solicitudes simultáneas | Correlativos únicos, consecutivos y sin colisión |
| ITAM-09 | Desplegar con solicitudes existentes | El siguiente correlativo continúa desde el máximo existente |
| ITAM-10 | Registrar primera evaluación | Se registra sin referencia de revisión |
| ITAM-11 | Registrar segunda evaluación | Se crea una revisión vinculada a la anterior; ambas quedan en historial |
| ITAM-12 | Dos evaluadores envían al mismo tiempo | Se serializa el proceso; la segunda operación trabaja sobre el estado actualizado |
| ITAM-13 | Repetir el seeder de catálogo | No cambia `created_at`, no reactiva categorías eliminadas |
| ITAM-14 | Ejecutar `php artisan route:list --name=itam` | Cada ruta ITAM aparece una sola vez |
| ITAM-15 | Ejecutar pruebas WMS existentes | Sin regresiones en rutas ni permisos WMS |

## Secuencia segura para validar

1. Realizar respaldo de la base de datos de pruebas.
2. Revisar las migraciones con `php artisan migrate --pretend`.
3. Ejecutar migraciones en una base de pruebas limpia y también en una copia con solicitudes ITAM existentes.
4. Ejecutar `php artisan migrate`.
5. Ejecutar `php artisan db:seed --class=ItamCatalogSeeder` solo en la base de pruebas y revisar el catálogo resultante.
6. Ejecutar `php artisan route:list --name=itam`.
7. Ejecutar `php artisan test` y las pruebas de aceptación anteriores.
8. Repetir las pruebas WMS relevantes.

**Estado:** cambios de código incorporados; pruebas automatizadas, migraciones y validación funcional aún no ejecutadas. No desplegar en producción hasta completar la validación y revisar el resultado de las migraciones sobre una copia de la base real.
