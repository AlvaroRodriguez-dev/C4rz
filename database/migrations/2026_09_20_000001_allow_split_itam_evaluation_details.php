<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('it_evaluacion_detalles', function (Blueprint $table) {
            $table->dropUnique('it_eval_det_uq');
            $table->index(
                ['evaluacion_id', 'solicitud_detalle_id'],
                'it_eval_det_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('it_evaluacion_detalles', function (Blueprint $table) {
            $table->dropIndex('it_eval_det_idx');
            $table->unique(
                ['evaluacion_id', 'solicitud_detalle_id'],
                'it_eval_det_uq'
            );
        });
    }
};
