<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wms_reubicaciones') || Schema::hasColumn('wms_reubicaciones', 'orden_trabajo_id')) {
            return;
        }

        Schema::table('wms_reubicaciones', function (Blueprint $table) {
            $table->foreignId('orden_trabajo_id')
                ->nullable()
                ->after('tipo')
                ->constrained('wms_ordenes_trabajo')
                ->nullOnDelete();

            $table->index('orden_trabajo_id', 'wms_reubicaciones_orden_trabajo_id_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('wms_reubicaciones') || ! Schema::hasColumn('wms_reubicaciones', 'orden_trabajo_id')) {
            return;
        }

        Schema::table('wms_reubicaciones', function (Blueprint $table) {
            $table->dropForeign(['orden_trabajo_id']);
            $table->dropIndex('wms_reubicaciones_orden_trabajo_id_index');
            $table->dropColumn('orden_trabajo_id');
        });
    }
};
