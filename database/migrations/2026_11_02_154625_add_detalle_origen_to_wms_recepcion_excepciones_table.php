<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_recepcion_excepciones', function (Blueprint $table) {
            $table->unsignedBigInteger('id_detalle_origen')
                ->nullable()
                ->after('wms_ingreso_id')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('wms_recepcion_excepciones', function (Blueprint $table) {
            $table->dropIndex([
                'id_detalle_origen',
            ]);

            $table->dropColumn('id_detalle_origen');
        });
    }
};