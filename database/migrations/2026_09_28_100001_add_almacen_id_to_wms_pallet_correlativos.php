<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_pallet_correlativos', function (Blueprint $table) {
            $table->foreignId('almacen_id')
                ->nullable()
                ->after('id')
                ->constrained('wms_almacenes')
                ->restrictOnDelete();

            $table->unique(
                ['almacen_id', 'anio'],
                'wms_pallet_correlativos_almacen_anio_unique'
            );
        });

        // El registro histórico existente corresponde al almacén 110
        // (wms_almacenes.id = 1) y conserva su correlativo actual.
        DB::table('wms_pallet_correlativos')
            ->where('anio', '26')
            ->whereNull('almacen_id')
            ->update(['almacen_id' => 1]);

        Schema::table('wms_pallet_correlativos', function (Blueprint $table) {
            $table->dropUnique('wms_pallet_correlativos_anio_unique');
            $table->foreignId('almacen_id')
                ->nullable(false)
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('wms_pallet_correlativos', function (Blueprint $table) {
            $table->dropUnique('wms_pallet_correlativos_almacen_anio_unique');
            $table->dropForeign(['almacen_id']);
            $table->dropColumn('almacen_id');
            $table->unique('anio');
        });
    }
};
