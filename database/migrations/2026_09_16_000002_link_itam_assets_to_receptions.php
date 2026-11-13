<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('it_activos', function (Blueprint $table) {
            $table->foreignId('recepcion_detalle_id')
                ->nullable()
                ->after('origen_linea')
                ->constrained('it_recepcion_detalles')
                ->nullOnDelete();

            $table->index(['recepcion_detalle_id', 'estado_id'], 'it_act_recep_estado_idx');
        });
    }

    public function down(): void
    {
        Schema::table('it_activos', function (Blueprint $table) {
            $table->dropForeign(['recepcion_detalle_id']);
            $table->dropIndex('it_act_recep_estado_idx');
            $table->dropColumn('recepcion_detalle_id');
        });
    }
};
