<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // documento_id debe almacenar el identificador documental WMS
        // (ej.: A010202609004), no el id interno autoincremental de wms_documentos.
        $foreignKey = DB::selectOne(
            "SELECT CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'wms_entregas_produccion'
               AND COLUMN_NAME = 'documento_id'
               AND REFERENCED_TABLE_NAME = 'wms_documentos'
             LIMIT 1"
        );

        if ($foreignKey) {
            DB::statement(sprintf(
                'ALTER TABLE wms_entregas_produccion DROP FOREIGN KEY `%s`',
                $foreignKey->CONSTRAINT_NAME
            ));
        }

        DB::statement(
            "ALTER TABLE wms_entregas_produccion MODIFY documento_id VARCHAR(50) NULL"
        );

        // Migra los valores históricos que todavía contengan el id interno.
        DB::statement(
            "UPDATE wms_entregas_produccion e
             INNER JOIN wms_documentos d ON d.id = CAST(e.documento_id AS UNSIGNED)
             SET e.documento_id = d.id_documento
             WHERE e.documento_id IS NOT NULL
               AND e.documento_id REGEXP '^[0-9]+$'"
        );

        $indexExists = DB::selectOne(
            "SELECT 1
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'wms_entregas_produccion'
               AND INDEX_NAME = 'wms_entregas_produccion_documento_id_index'
             LIMIT 1"
        );

        if (!$indexExists) {
            Schema::table('wms_entregas_produccion', function (Blueprint $table) {
                $table->index('documento_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('wms_entregas_produccion', function (Blueprint $table) {
            $table->dropIndex(['documento_id']);
        });

        // La reversión vuelve a almacenar el id interno del documento.
        DB::statement(
            "UPDATE wms_entregas_produccion e
             INNER JOIN wms_documentos d ON d.id_documento = e.documento_id
             SET e.documento_id = d.id"
        );

        DB::statement(
            "ALTER TABLE wms_entregas_produccion MODIFY documento_id BIGINT UNSIGNED NULL"
        );
    }
};
