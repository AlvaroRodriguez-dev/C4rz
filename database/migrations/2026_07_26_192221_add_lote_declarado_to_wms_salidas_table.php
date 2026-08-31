<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_salidas', function (Blueprint $table) {
            $table->string('lote_declarado', 30)->nullable()->after('clote');
            $table->boolean('es_excepcion_lote')->default(false)->after('lote_declarado');
        });

        // Backfill: para registros históricos, el lote declarado es el mismo que el físico
        DB::table('wms_salidas')->whereNull('lote_declarado')->update([
            'lote_declarado' => DB::raw('clote'),
        ]);
    }

    public function down(): void
    {
        Schema::table('wms_salidas', function (Blueprint $table) {
            $table->dropColumn(['lote_declarado', 'es_excepcion_lote']);
        });
    }
};