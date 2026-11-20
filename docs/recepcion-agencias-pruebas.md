# Recepción física de agencias — guía de pruebas

Rama: `recepcion-agencias`

## Preparación

1. Actualizar el código a la rama `recepcion-agencias`.
2. Ejecutar `php artisan migrate`.
3. Confirmar que el usuario de pruebas tiene el permiso `wms.ingresos.create`.
4. Para crear zonas temporales, el usuario también necesita `wms.configurar`.
5. En WMS → Recepción agencias, crear al menos dos zonas temporales, por ejemplo `REC-01` y `REC-02`.
6. Confirmar que existen ubicaciones definitivas activas de tipo `NORMAL` en el almacén.
7. Confirmar que las conexiones `faboce2026` y `sisinvconsolidado2026` están operativas.

## Fuente de documentos

La búsqueda toma documentos tipo 1, 2 y 3 de `faboce2026.log_registro`, filtrados por `agencia` igual al código del almacén operativo. El documento solo aparece si existen salidas WMS asociadas en `wms_salidas`.

Los pallets y sus líneas esperadas se cargan desde `wms_salidas`, agrupando por documento y número original de pallet.

## Flujo de prueba

1. Crear una recepción para un camión y asociar dos documentos logísticos.
2. Confirmar que todos los pallets de ambos documentos aparecen en la recepción.
3. Descargar pallets en distintas ubicaciones temporales.
4. Seleccionar un pallet mediante búsqueda Select2 o lectura QR.
5. Verificar código, lote y cantidad física de cada línea.
6. Confirmar un pallet sin diferencias. Debe pasar a `verificado`.
7. Registrar una diferencia en otro pallet (cantidad, producto, lote o daño). Debe pasar a `observado`.
8. Ubicar e ingresar el pallet conforme. Debe pasar a `ingresado` y generar filas `wms_ingresos.tipo_ingreso = recepcion_agencia`.
9. Comprobar que el pallet observado no puede ingresar sin corrección o autorización excepcional.
10. Con usuario WMS-ADMIN o SIS-ADMIN, autorizar la excepción y comprobar que ese pallet puede ingresar con sus cantidades físicas.
11. Cerrar la recepción dejando, si corresponde, otros pallets pendientes. La cabecera pasa a `cerrada`, pero los estados individuales y las observaciones permanecen registrados.

## Consultas de control

Resumen por recepción:

```sql
SELECT
    r.id,
    r.estado,
    COUNT(p.id) AS total_pallets,
    SUM(p.estado = 'ingresado') AS ingresados,
    SUM(p.estado = 'observado') AS observados,
    SUM(p.estado = 'pendiente_descarga') AS pendientes_descarga
FROM wms_recepciones_agencia r
LEFT JOIN wms_recepcion_agencia_pallets p ON p.recepcion_id = r.id
GROUP BY r.id, r.estado
ORDER BY r.id DESC;
```

Ingresos definitivos de agencia:

```sql
SELECT
    id, rdocum, doc_origen, pallet, codigo, clote,
    cantidad, almacen, galpon, ubicacion, created_at
FROM wms_ingresos
WHERE tipo_ingreso = 'recepcion_agencia'
ORDER BY id DESC;
```

Observaciones y autorizaciones:

```sql
SELECT
    o.id, p.pallet, o.tipo, o.descripcion, o.estado,
    o.autorizado_por, o.autorizado_at
FROM wms_recepcion_agencia_observaciones o
JOIN wms_recepcion_agencia_pallets p ON p.id = o.pallet_id
ORDER BY o.id DESC;
```

## Reglas de aceptación

- Descargar o verificar un pallet no crea stock.
- Un pallet conforme puede ingresar aunque otro pallet del mismo camión tenga observaciones.
- Un pallet observado no ingresa sin corrección o autorización.
- La autorización aplica únicamente al pallet observado.
- El ingreso conserva el número original de pallet.
- El saldo físico se actualiza únicamente al crear los movimientos en `wms_ingresos`.
- Cerrar la recepción no convierte automáticamente los pallets pendientes en ingresados.
- Un documento puede distribuirse entre varias recepciones/camiones si todavía quedan pallets pendientes; un mismo pallet de ese documento no puede asociarse dos veces.
