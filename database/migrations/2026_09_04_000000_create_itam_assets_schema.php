<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('it_ubicaciones', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('descripcion', 150);
            $table->foreignId('ubicacion_padre_id')->nullable()->constrained('it_ubicaciones')->nullOnDelete();
            $table->unsignedInteger('nivel_jerarquia')->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('it_tipos_activo', function (Blueprint $table) {
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

        Schema::create('it_marcas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150)->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('it_estados', function (Blueprint $table) {
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

        Schema::create('it_modelos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marca_id')->constrained('it_marcas')->restrictOnDelete();
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

        Schema::create('it_componentes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('tipo_componente', 80);
            $table->foreignId('marca_id')->nullable()->constrained('it_marcas')->nullOnDelete();
            $table->string('modelo', 150)->nullable();
            $table->string('capacidad', 100)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('it_activos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_ti', 30)->unique();
            $table->foreignId('tipo_activo_id')->constrained('it_tipos_activo')->restrictOnDelete();
            $table->foreignId('marca_id')->nullable()->constrained('it_marcas')->restrictOnDelete();
            $table->foreignId('modelo_id')->nullable()->constrained('it_modelos')->restrictOnDelete();
            $table->string('numero_serie', 150)->nullable();
            $table->string('numero_parte', 150)->nullable();
            $table->foreignId('estado_id')->constrained('it_estados')->restrictOnDelete();
            $table->foreignId('ubicacion_id')->constrained('it_ubicaciones')->restrictOnDelete();
            $table->unsignedBigInteger('custodio_id')->nullable()->index();
            $table->unsignedBigInteger('usuario_asignado_id')->nullable()->index();
            $table->date('fecha_adquisicion')->nullable();
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
            $table->unique(['tipo_activo_id', 'numero_serie'], 'it_activo_tipo_serial_uq');
            $table->index(['origen_documento', 'origen_linea'], 'it_activo_origen_idx');
        });

        Schema::create('it_caracteristicas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activo_id')->constrained('it_activos')->cascadeOnDelete();
            $table->string('nombre_caracteristica', 100);
            $table->text('valor')->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['activo_id', 'nombre_caracteristica', 'fecha_fin'], 'it_caract_activo_nombre_fin_idx');
        });

        Schema::create('it_componentes_instalados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activo_id')->constrained('it_activos')->cascadeOnDelete();
            $table->foreignId('componente_id')->constrained('it_componentes')->restrictOnDelete();
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
            $table->index(['activo_id', 'fecha_retiro'], 'it_comp_activo_retiro_idx');
            $table->index(['componente_id', 'numero_serie_componente'], 'it_comp_serial_idx');
        });

        Schema::create('it_accesorios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activo_id')->constrained('it_activos')->cascadeOnDelete();
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
            $table->index(['activo_id', 'fecha_fin'], 'it_accesorio_activo_fin_idx');
        });

        Schema::create('it_relaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activo_origen_id')->constrained('it_activos')->cascadeOnDelete();
            $table->foreignId('activo_destino_id')->constrained('it_activos')->cascadeOnDelete();
            $table->string('tipo_relacion', 50);
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['activo_origen_id', 'fecha_fin'], 'it_rel_origen_fin_idx');
            $table->index(['activo_destino_id', 'fecha_fin'], 'it_rel_destino_fin_idx');
            $table->unique(['activo_origen_id', 'activo_destino_id', 'tipo_relacion', 'fecha_inicio'], 'it_relacion_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('it_relaciones');
        Schema::dropIfExists('it_accesorios');
        Schema::dropIfExists('it_componentes_instalados');
        Schema::dropIfExists('it_caracteristicas');
        Schema::dropIfExists('it_activos');
        Schema::dropIfExists('it_componentes');
        Schema::dropIfExists('it_modelos');
        Schema::dropIfExists('it_estados');
        Schema::dropIfExists('it_marcas');
        Schema::dropIfExists('it_tipos_activo');
        Schema::dropIfExists('it_ubicaciones');
    }
};
