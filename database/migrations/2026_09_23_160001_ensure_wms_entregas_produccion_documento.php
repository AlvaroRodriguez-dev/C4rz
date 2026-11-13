<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('wms_entregas_produccion', 'documento_id')) {
            Schema::table('wms_entregas_produccion', function (Blueprint $table) {
                $table->foreignId('documento_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('wms_documentos')
                    ->restrictOnDelete();

                $table->unique('documento_id');
            });
        }

        if (Schema::hasColumn('wms_entregas_produccion', 'codigo')) {
            Schema::table('wms_entregas_produccion', function (Blueprint $table) {
                $table->dropUnique(['codigo']);
                $table->dropColumn('codigo');
            });
        }

        Schema::table('wms_entregas_produccion', function (Blueprint $table) {
            $table->index(['documento_id', 'almacen_id']);
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('wms_entregas_produccion', 'documento_id')) {
            Schema::table('wms_entregas_produccion', function (Blueprint $table) {
                $table->dropIndex(['documento_id', 'almacen_id']);
                $table->dropUnique(['documento_id']);
                $table->dropForeign(['documento_id']);
                $table->dropColumn('documento_id');
            });
        }

        if (!Schema::hasColumn('wms_entregas_produccion', 'codigo')) {
            Schema::table('wms_entregas_produccion', function (Blueprint $table) {
                $table->string('codigo', 30)->unique()->nullable()->after('id');
            });
        }
    }
};
