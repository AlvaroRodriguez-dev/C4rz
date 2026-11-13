<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_hu_verificaciones', function (Blueprint $table) {
            // MySQL necesita otro índice que respalde la FK antes de quitar el UNIQUE.
            $table->index('hu_id', 'wms_hu_verificaciones_hu_id_id_index');
        });

        Schema::table('wms_hu_verificaciones', function (Blueprint $table) {
            $table->dropUnique('wms_hu_verificaciones_hu_id_unique');
        });
    }

    public function down(): void
    {
        // No se recrea el UNIQUE: el historial puede contener varias verificaciones por HU.
        Schema::table('wms_hu_verificaciones', function (Blueprint $table) {
            $table->dropIndex('wms_hu_verificaciones_hu_id_id_index');
        });
    }
};
