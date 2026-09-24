<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_entregas_detalle', function (Blueprint $table) {
            $table->unsignedInteger('cantidad_fisica')
                ->nullable()
                ->after('cantidad_declarada');
        });

        Schema::table('wms_entregas_produccion', function (Blueprint $table) {
            $table->timestamp('verificado_at')->nullable()->after('fecha_recepcion');
            $table->foreignId('verificado_id')
                ->nullable()
                ->after('verificado_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('wms_entregas_produccion', function (Blueprint $table) {
            $table->dropForeign(['verificado_id']);
            $table->dropColumn(['verificado_at', 'verificado_id']);
        });

        Schema::table('wms_entregas_detalle', function (Blueprint $table) {
            $table->dropColumn('cantidad_fisica');
        });
    }
};
