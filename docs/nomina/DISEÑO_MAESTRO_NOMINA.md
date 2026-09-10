# Diseño del Maestro de Nómina

## 1. Objetivo

Definir el maestro propio del módulo de Nómina de C4rz sin duplicar el maestro laboral de RRHH.

La fuente externa `rrhh_personal` se utilizará únicamente para identificar al trabajador mediante `LICENSE` y obtener su nombre completo.

## 2. Principio de arquitectura

> RRHH identifica a la persona. Nómina administra la configuración económica, laboral y de pago necesaria para calcular la nómina.

No se copiarán a Nómina campos de `rrhh_personal` que no hayan sido definidos expresamente como parte del dominio de Nómina.

## 3. Fuente externa de identificación

Base PostgreSQL: conexión `pgsql_rrhh`.

Tabla: `[PG_RRHH_SCHEMA].rrhh_personal`.

Campos autorizados para esta versión:

- `LICENSE`: identificador del trabajador.
- `NAME`: nombre.
- `LASTNAME`: apellidos.
- `DELETED_AT`: solamente para determinar si el registro está activo.

El identificador de Nómina debe corresponder al `LICENSE` de RRHH.

## 4. Información que pertenece a Nómina

El prototipo deberá rediseñar y administrar internamente, como mínimo, las siguientes áreas:

### Identificación y relación laboral

- License.
- Nombre completo como snapshot cuando corresponda.
- Fecha de ingreso para efectos de nómina, si se define como dato propio.
- Estado dentro del módulo de Nómina.
- Configuración de vigencia.

### Configuración salarial

- Haber básico.
- Tipo/modalidad de remuneración.
- Categoría.
- Bono fijo.
- Bono de categoría.
- Otros conceptos permanentes.
- Reglas particulares de remuneración.

### Aportes y descuentos

- AFP/gestora.
- NUA/CUA.
- Parámetros de RC-IVA.
- Cuota sindical.
- Comedor.
- Anticipos.
- Descuentos judiciales.
- Otros descuentos configurables.

### Datos de pago

- Institución bancaria.
- Cuenta bancaria.
- Tipo de pago.
- Indicador de depósito/efectivo.

### Otros parámetros de nómina

- Centro de costo.
- Clasificación laboral propia de Nómina.
- Configuraciones necesarias para beneficios sociales, vacaciones y otros cálculos.

## 5. Vigencia histórica

Los datos económicos y laborales no deben sobrescribirse sin conservar su vigencia.

Ejemplo:

| Vigencia | Haber básico |
|---|---:|
| 01/01/2026 - 30/06/2026 | 4.500 |
| 01/07/2026 - vigente | 5.000 |

Una liquidación histórica debe poder reconstruirse utilizando la configuración vigente para el período procesado.

## 6. Separación de responsabilidades

### `rrhh_personal`

Fuente de identificación:

`LICENSE + NAME + LASTNAME`

### Maestro/configuración de Nómina

Fuente de configuración económica y laboral propia del módulo.

### `nomina_entradas`

Representa la participación del trabajador en un período específico y conserva los datos necesarios para reconstruir la entrada al proceso de cálculo.

### `nomina_resultados` / `nomina_resultado_detalles`

Representan el resultado de una corrida/versionado de nómina y no deben utilizarse como maestro.

## 7. Regla de no duplicación

No crear una tabla `empleados` genérica que replique `rrhh_personal`.

Si Nómina necesita almacenar atributos adicionales del trabajador, se debe crear una estructura específica del dominio de Nómina y relacionarla mediante `LICENSE`.

## 8. Próximo paso

Antes de implementar nuevas tablas, realizar el mapeo definitivo de la hoja `Base D. Personal` y de las hojas `Planilla Interna` y `Planilla Fiscal` de mayo 2026, clasificando cada campo como:

1. Identificación proveniente de RRHH.
2. Dato maestro propio de Nómina.
3. Dato histórico/snapshot del período.
4. Concepto de ingreso.
5. Concepto de descuento/aporte.
6. Dato calculado.
7. Parámetro general.
8. Dato de pago.

No implementar campos solamente porque existan en el Excel; cada campo debe tener una responsabilidad definida en el modelo.
