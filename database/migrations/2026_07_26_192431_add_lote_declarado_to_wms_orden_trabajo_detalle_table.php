<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_orden_trabajo_detalle', function (Blueprint $table) {
            $table->string('lote_declarado', 30)->nullable()->after('clote');
            $table->boolean('es_excepcion_lote')->default(false)->after('lote_declarado');
        });
    }

    public function down(): void
    {
        Schema::table('wms_orden_trabajo_detalle', function (Blueprint $table) {
            $table->dropColumn(['lote_declarado', 'es_excepcion_lote']);
        });
    }
};