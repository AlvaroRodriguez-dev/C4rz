<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('wms_pallet_correlativos', 'almacen_id')) {
            Schema::table('wms_pallet_correlativos', function (Blueprint $table) {
                $table->unsignedBigInteger('almacen_id')->nullable()->after('id');
            });
        }

        // La fila histórica existente corresponde al almacén 110.
        if (Schema::hasColumn('wms_pallet_correlativos', 'almacen_id')) {
            DB::table('wms_pallet_correlativos')
                ->whereNull('almacen_id')
                ->where('anio', '26')
                ->update(['almacen_id' => 1]);
        }

        $indices = collect(DB::select("SHOW INDEX FROM wms_pallet_correlativos"))
            ->pluck('Key_name')
            ->unique()
            ->values()
            ->all();

        if (in_array('wms_pallet_correlativos_anio_unique', $indices, true)) {
            Schema::table('wms_pallet_correlativos', function (Blueprint $table) {
                $table->dropUnique('wms_pallet_correlativos_anio_unique');
            });
        }

        $indices = collect(DB::select("SHOW INDEX FROM wms_pallet_correlativos"))
            ->pluck('Key_name')
            ->unique()
            ->values()
            ->all();

        if (!in_array('wms_pallet_correlativos_almacen_anio_unique', $indices, true)) {
            Schema::table('wms_pallet_correlativos', function (Blueprint $table) {
                $table->unique(
                    ['almacen_id', 'anio'],
                    'wms_pallet_correlativos_almacen_anio_unique'
                );
            });
        }

        if (!in_array('wms_pallet_correlativos_almacen_id_foreign', $indices, true)) {
            Schema::table('wms_pallet_correlativos', function (Blueprint $table) {
                $table->foreign('almacen_id', 'wms_pallet_correlativos_almacen_id_foreign')
                    ->references('id')
                    ->on('wms_almacenes')
                    ->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('wms_pallet_correlativos', 'almacen_id')) {
            Schema::table('wms_pallet_correlativos', function (Blueprint $table) {
                $table->dropForeign('wms_pallet_correlativos_almacen_id_foreign');
                $table->dropUnique('wms_pallet_correlativos_almacen_anio_unique');
                $table->dropColumn('almacen_id');
            });
        }

        $indices = collect(DB::select("SHOW INDEX FROM wms_pallet_correlativos"))
            ->pluck('Key_name')
            ->unique()
            ->values()
            ->all();

        if (!in_array('wms_pallet_correlativos_anio_unique', $indices, true)) {
            Schema::table('wms_pallet_correlativos', function (Blueprint $table) {
                $table->unique('anio', 'wms_pallet_correlativos_anio_unique');
            });
        }
    }
};
