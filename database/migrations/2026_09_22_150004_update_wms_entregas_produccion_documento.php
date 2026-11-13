<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_entregas_produccion', function (Blueprint $table) {
            $table->foreignId('documento_id')
                ->nullable()
                ->after('id')
                ->constrained('wms_documentos', 'id')
                ->restrictOnDelete();
        });

        Schema::table('wms_entregas_produccion', function (Blueprint $table) {
            $table->dropUnique('wms_entregas_produccion_codigo_unique');
            $table->dropColumn('codigo');
        });

        Schema::table('wms_entregas_produccion', function (Blueprint $table) {
            $table->unique('documento_id');
        });
    }

    public function down(): void
    {
        Schema::table('wms_entregas_produccion', function (Blueprint $table) {
            $table->dropUnique('wms_entregas_produccion_documento_id_unique');
            $table->dropForeign(['documento_id']);
            $table->dropColumn('documento_id');

            $table->string('codigo', 30)->nullable()->unique()->after('id');
        });
    }
};