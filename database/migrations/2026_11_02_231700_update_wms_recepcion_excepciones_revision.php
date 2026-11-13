<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_recepcion_excepciones', function (Blueprint $table) {
            $table->foreignId('autorizado_por')->nullable()->change();
            $table->timestamp('autorizado_at')->nullable()->change();

            $table->string('estado_revision', 20)->default('pendiente')->after('autorizado_at');
            $table->foreignId('revisado_por')->nullable()->after('estado_revision')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('revisado_at')->nullable()->after('revisado_por');
            $table->string('comentario_revision', 1000)->nullable()->after('revisado_at');
        });

        // Las excepciones históricas ya registradas con autorizador se conservan
        // como autorizadas; únicamente las nuevas nacen pendientes de revisión.
        DB::table('wms_recepcion_excepciones')
            ->whereNotNull('autorizado_por')
            ->update(['estado_revision' => 'aceptada']);
    }

    public function down(): void
    {
        Schema::table('wms_recepcion_excepciones', function (Blueprint $table) {
            $table->dropForeign(['revisado_por']);
            $table->dropColumn([
                'estado_revision',
                'revisado_por',
                'revisado_at',
                'comentario_revision',
            ]);

            $table->foreignId('autorizado_por')->nullable(false)->change();
            $table->timestamp('autorizado_at')->nullable(false)->change();
        });
    }
};
