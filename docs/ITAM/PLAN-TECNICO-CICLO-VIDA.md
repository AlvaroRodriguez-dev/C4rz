# ITAM — Plan técnico de ciclo de vida de activos

## Decisiones funcionales aprobadas

- Alcance integral: desde la identificación/alta hasta la baja.
- Origen de activos: adquisiciones e inventario inicial.
- Primer entregable: inventario individual y ficha del activo.
- Base de trabajo: `itam-estabilizacion-tecnologicos-nov2026`.
- Rama de desarrollo: `itam-inventario-activos`.

## Principios

1. Reutilizar `it_activos`, `it_tipos_activo`, `it_marcas`, `it_modelos`, `it_estados`, `it_ubicaciones`, `it_caracteristicas`, `it_componentes_instalados`, `it_accesorios` e `it_relaciones`.
2. No duplicar el modelo de producto de Rackflash Lite.
3. Cada activo tecnológico físico tendrá un registro individual y un código ITAM único.
4. El inventario inicial y la adquisición son orígenes distintos del activo; ninguno debe simular una compra.
5. Las operaciones que cambian custodia, ubicación o estado deben generar movimientos históricos en fases posteriores.
6. No eliminar físicamente activos ni su historial. Las anulaciones requerirán justificación y auditoría.
7. Separar estado físico, estado operativo y situación administrativa en el diseño objetivo. La tabla `it_estados` actual representa la situación operativa de asignabilidad; se evaluará una ampliación/migración antes de introducir los otros ejes.
8. RRHH será la fuente de verdad de personal; OCS Inventory será fuente de descubrimiento técnico, no el maestro patrimonial.

## Hoja de ruta

### Fase 1 — Inventario individual (entrega actual)
- Listado con búsqueda y filtros básicos.
- Alta de activo con tipo, marca, modelo, serie, ubicación, fecha/costo, garantía y origen.
- Si el origen es ADQUISICION, selección obligatoria de una línea de recepción con saldo pendiente; vínculo mediante `recepcion_detalle_id` y descuento transaccional de una unidad pendiente.
- Código ITAM generado por servidor.
- Ficha individual de consulta.
- Catálogo inicial de estados.
- Permisos separados para consultar y registrar.
- Validaciones de serie por tipo de activo y protección contra duplicidad.

### Fase 2 — Asignación, custodia y devolución
- Entregas y devoluciones con responsable RRHH.
- Actas PDF y accesorios entregados/devueltos.
- Historial inmutable de custodias.
- Bloqueo transaccional del activo para impedir asignaciones concurrentes.

### Fase 3 — Movimientos y ubicaciones
- Traslados entre agencias, plantas, oficinas y almacenes.
- Historial origen/destino, solicitante, autorizador y recepción.
- Ubicación actual como dato de consulta rápida derivado del último movimiento confirmado.

### Fase 4 — Mantenimiento y garantías
- Mantenimiento preventivo/correctivo, diagnóstico, costos, proveedor y evidencias.
- Alertas de vencimiento de garantía y mantenimiento programado.
- Historial de componentes instalados y retirados.

### Fase 5 — Inventario físico y OCS
- Campañas de verificación, escaneo QR, diferencias y conciliación.
- Vinculación controlada de activo ITAM con dispositivo OCS.
- Detección de activos ITAM no detectados y dispositivos OCS sin activo ITAM.

### Fase 6 — Bajas y disposición final
- Solicitud, evaluación técnica, autorización y respaldo documental.
- Baja lógica/administrativa sin borrar historial.
- Reportes de activos dados de baja y motivo.

### Fase 7 — Indicadores y reportes
- Inventario por tipo, ubicación, estado, custodio y antigüedad.
- Garantías por vencer, activos sin custodio y discrepancias OCS.
- Indicadores ejecutivos y exportación.

## Modelo de datos objetivo

Se reutilizan las tablas existentes de activos y catálogos. Se incorporarán en fases separadas, con migraciones aditivas y pruebas:

- `it_asignaciones` y detalle de accesorios/condición de entrega.
- `it_movimientos` para traslados y cambios de ubicación.
- `it_mantenimientos` y sus intervenciones/evidencias.
- `it_bajas` con autorización y documentos.
- `it_activo_ocs` o identificador externo equivalente para vinculación con OCS.
- Auditoría explícita de operaciones sensibles.

## Criterios de aceptación de la Fase 1

- Un usuario autorizado puede consultar el inventario y la ficha.
- Un usuario autorizado puede registrar un activo desde inventario inicial o adquisición.
- El servidor genera el código; nunca se acepta un código enviado por navegador.
- Un número de serie no se duplica para el mismo tipo de activo.
- Si el tipo exige serie, el dato es obligatorio.
- Tipo, marca, modelo, estado y ubicación deben existir y estar activos.
- El activo nuevo inicia en estado DISPONIBLE y sin custodio.
- Para adquisiciones, la línea de recepción queda vinculada y sus cantidades `cantidad_identificada` / `cantidad_pendiente` se actualizan dentro de la misma transacción.
- El alta queda asociada al usuario autenticado.
- No se modifican ramas protegidas ni se integra automáticamente a otra rama.

## Validación pendiente en entorno local

La conexión GitHub permite preparar código, pero no ejecutar la aplicación ni las pruebas dentro del entorno local del usuario. Antes de integrar:
1. Respaldar base de pruebas.
2. Ejecutar migraciones y seeders indicados en la rama.
3. Ejecutar `php artisan route:list --name=itam`.
4. Ejecutar `php artisan test`.
5. Probar alta desde ambos orígenes, duplicidad de serie, permisos y ficha.
6. Revisar diferencias contra la rama base y abrir PR solo después de validar.
