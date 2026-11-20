
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Bloqueo lógico por recepción de SAS-ERP.
         * La fila se bloqueará dentro de la transacción de registro.
         */
        Schema::create('wms_recepcion_bloqueos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_recepcion');
            $table->timestamps();

            $table->unique(
                'id_recepcion',
                'uq_wms_recepcion_bloqueo'
            );
        });

        /*
         * Relación entre cada ingreso WMS y las filas originales
         * de log_registro_detalle en faboce2026.
         *
         * Una fila de origen puede distribuirse entre varios lotes
         * físicos; por eso no se declara único source_detail_id.
         */
        Schema::create('wms_recepcion_detalles', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('wms_ingreso_id');
            $table->unsignedBigInteger('id_detalle_origen');
            $table->unsignedInteger('cantidad_asignada');

            $table->timestamps();

            $table->foreign('wms_ingreso_id', 'fk_wms_recep_det_ingreso')
                ->references('id')
                ->on('wms_ingresos')
                ->cascadeOnDelete();

            $table->unique(
                ['wms_ingreso_id', 'id_detalle_origen'],
                'uq_wms_recep_ingreso_detalle'
            );

            $table->index(
                'id_detalle_origen',
                'idx_wms_recep_det_origen'
            );
        });

        /*
         * Auditoría independiente de las dos excepciones:
         * exceso de cantidad y discrepancia de lote.
         *
         * Se guarda el autorizador real, no un nombre de usuario
         * fijo ni una autorización implícita por rol.
         */
        Schema::create('wms_recepcion_excepciones', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('wms_ingreso_id');

            $table->enum('tipo', [
                'exceso_cantidad',
                'discrepancia_lote',
            ]);

            $table->string('lote_origen', 30)->nullable();
            $table->string('lote_fisico', 30)->nullable();

            $table->unsignedInteger('cantidad_origen')->nullable();
            $table->unsignedInteger('cantidad_fisica')->nullable();

            $table->string('observacion', 500);

            $table->foreignId('autorizado_por')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('autorizado_at');
            $table->timestamps();

            $table->foreign(
                'wms_ingreso_id',
                'fk_wms_recep_exc_ingreso'
            )->references('id')
                ->on('wms_ingresos')
                ->cascadeOnDelete();

            $table->index(
                ['wms_ingreso_id', 'tipo'],
                'idx_wms_recep_exc_tipo'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_recepcion_excepciones');
        Schema::dropIfExists('wms_recepcion_detalles');
        Schema::dropIfExists('wms_recepcion_bloqueos');
    }
};
