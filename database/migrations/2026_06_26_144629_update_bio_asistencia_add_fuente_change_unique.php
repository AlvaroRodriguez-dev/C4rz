<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        // ── 1. Desactivar FK checks (más simple) ────────────────
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            Schema::table('bio_asistencia', function (Blueprint $table) {
                // ── 2. Eliminar unique key ──────────────────────────
                $table->dropUnique('bio_asistencia_unique');

                // ── 3. Modificar columna uid ────────────────────────
                $table->integer('uid')->nullable()->change();

                // ── 4. Agregar nuevas columnas ──────────────────────
                $table->enum('fuente', ['online', 'usb'])->default('online')->after('type');
                $table->string('archivo_origen', 255)->nullable()->after('fuente');

                // ── 5. Nueva unique key ─────────────────────────────
                $table->unique(['biometrico_id', 'user_id', 'timestamp'], 'bio_asistencia_unique');
            });
        } finally {
            // ── 6. Reactivar FK checks ──────────────────────────────
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            Schema::table('bio_asistencia', function (Blueprint $table) {
                $table->dropUnique('bio_asistencia_unique');
                $table->integer('uid')->nullable(false)->change();
                $table->dropColumn(['fuente', 'archivo_origen']);
                $table->unique(['biometrico_id', 'uid'], 'bio_asistencia_unique');
            });
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
};