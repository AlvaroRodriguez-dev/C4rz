<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Modelo fisico base del modulo ITAM.
     *
     * Nota de integracion:
     * - custodio_id y usuario_asignado_id apuntan conceptualmente a rrhh_personal
     *   en PostgreSQL y se almacenan como referencias externas; no se crean FKs
     *   MySQL porque pertenecen a otra conexion/base de datos.
     * - proveedor_id se mantiene como referencia al maestro de proveedores de
     *   SAS-ERP. No se crea FK hasta identificar la tabla maestra local/expuesta.
     */
    public function up(): void
    {
        // 1. Maestros base
        Schema::create('activos_ti_ubicaciones', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('descripcion', 150);
            $table->foreignId('ubicacion_padre_id')
                ->nullable()
                ->constrained('activos_ti_ubicaciones')
                ->nullOnDelete();
            $table->unsignedInteger('nivel_jerarquia')->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('activos_ti_tipos_activo', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('descripcion', 150);
            $table->boolean('requiere_serial')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('activos_ti_marcas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150)->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('activos_ti_estados', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('descripcion', 150);
            $table->boolean('permite_asignacion')->default(false);
            $table->boolean('permite_traslado')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('activos_ti_modelos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marca_id')->constrained('activos_ti_marcas')->restrictOnDelete();
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->json('especificaciones')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['marca_id', 'nombre']);
        });

        Schema::create('componentes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('tipo_componente', 80);
            $table->foreignId('marca_id')->nullable()->constrained('activos_ti_marcas')->nullOnDelete();
            $table->string('modelo', 150)->nullable();
            $table->string('capacidad', 100)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        // 2. Maestro principal de activos
        Schema::create('activos_ti', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_ti', 30)->unique();
            $table->foreignId('tipo_activo_id')->constrained('activos_ti_tipos_activo')->restrictOnDelete();
            $table->foreignId('marca_id')->nullable()->constrained('activos_ti_marcas')->restrictOnDelete();
            $table->foreignId('modelo_id')->nullable()->constrained('activos_ti_modelos')->restrictOnDelete();
            $table->string('numero_serie', 150)->nullable();
            $table->string('numero_parte', 150)->nullable();
            $table->foreignId('estado_id')->constrained('activos_ti_estados')->restrictOnDelete();
            $table->foreignId('ubicacion_id')->constrained('activos_ti_ubicaciones')->restrictOnDelete();

            // Referencias externas a rrhh_personal en PostgreSQL.
            $table->unsignedBigInteger('custodio_id')->nullable()->index();
            $table->unsignedBigInteger('usuario_asignado_id')->nullable()->index();

            $table->date('fecha_adquisicion')->nullable();
            // Referencia externa al maestro de proveedores de SAS-ERP.
            $table->unsignedBigInteger('proveedor_id')->nullable()->index();
            $table->decimal('costo', 15, 2)->nullable();
            $table->date('garantia_fecha')->nullable();
            $table->string('origen_documento', 50)->nullable();
            $table->unsignedBigInteger('origen_linea')->nullable();
            $table->date('fecha_alta')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            // Permite el mismo serial en tipos distintos, pero no duplicarlo
            // dentro del mismo tipo. Los NULL no colisionan en MySQL.
            $table->unique(['tipo_activo_id', 'numero_serie']);
            $table->index(['origen_documento', 'origen_linea']);
        });

        // 3. Caracteristicas historicas del activo
        Schema::create('activos_ti_caracteristicas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activo_id')->constrained('activos_ti')->cascadeOnDelete();
            $table->string('nombre_caracteristica', 100);
            $table->text('valor')->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['activo_id', 'nombre_caracteristica', 'fecha_fin']);
        });

        // 4. Componentes instalados y su historial
        Schema::create('activos_ti_componentes_instalados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activo_id')->constrained('activos_ti')->cascadeOnDelete();
            $table->foreignId('componente_id')->constrained('componentes')->restrictOnDelete();
            $table->string('numero_serie_componente', 150)->nullable();
            $table->date('fecha_instalacion');
            $table->date('fecha_retiro')->nullable();
            $table->string('tipo_operacion', 50);
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('costo', 15, 2)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['activo_id', 'fecha_retiro']);
            $table->index(['componente_id', 'numero_serie_componente']);
        });

        // 5. Accesorios asociados con vigencia historica
        Schema::create('activos_ti_accesorios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activo_id')->constrained('activos_ti')->cascadeOnDelete();
            $table->string('nombre_accesorio', 150);
            $table->unsignedInteger('cantidad')->default(1);
            $table->decimal('valor_unitario', 15, 2)->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['activo_id', 'fecha_fin']);
        });

        // 6. Relaciones entre activos
        Schema::create('activos_ti_relaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activo_origen_id')->constrained('activos_ti')->cascadeOnDelete();
            $table->foreignId('activo_destino_id')->constrained('activos_ti')->cascadeOnDelete();
            $table->string('tipo_relacion', 50);
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['activo_origen_id', 'fecha_fin']);
            $table->index(['activo_destino_id', 'fecha_fin']);
            $table->unique(['activo_origen_id', 'activo_destino_id', 'tipo_relacion', 'fecha_inicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activos_ti_relaciones');
        Schema::dropIfExists('activos_ti_accesorios');
        Schema::dropIfExists('activos_ti_componentes_instalados');
        Schema::dropIfExists('activos_ti_caracteristicas');
        Schema::dropIfExists('activos_ti');
        Schema::dropIfExists('componentes');
        Schema::dropIfExists('activos_ti_modelos');
        Schema::dropIfExists('activos_ti_estados');
        Schema::dropIfExists('activos_ti_marcas');
        Schema::dropIfExists('activos_ti_tipos_activo');
        Schema::dropIfExists('activos_ti_ubicaciones');
    }
};
