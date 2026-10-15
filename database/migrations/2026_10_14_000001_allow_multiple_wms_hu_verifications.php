<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_hu_verificaciones', function (Blueprint $table) {
            $table->dropUnique('wms_hu_verificaciones_hu_id_unique');
            $table->index(['hu_id', 'id'], 'wms_hu_verificaciones_hu_id_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('wms_hu_verificaciones', function (Blueprint $table) {
            $table->dropIndex('wms_hu_verificaciones_hu_id_id_index');
            // No se recrea la restricción única: podría haber varias verificaciones históricas.
        });
    }
};
