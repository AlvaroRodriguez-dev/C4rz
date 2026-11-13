<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('it_evaluaciones', function (Blueprint $table) {
            $table->foreignId('revision_de_id')
                ->nullable()
                ->after('solicitud_id')
                ->constrained('it_evaluaciones')
                ->nullOnDelete();

            // Una evaluación solo puede ser sustituida directamente por una revisión.
            $table->unique('revision_de_id', 'it_evaluaciones_revision_de_uq');
        });
    }

    public function down(): void
    {
        Schema::table('it_evaluaciones', function (Blueprint $table) {
            $table->dropUnique('it_evaluaciones_revision_de_uq');
            $table->dropConstrainedForeignId('revision_de_id');
        });
    }
};
