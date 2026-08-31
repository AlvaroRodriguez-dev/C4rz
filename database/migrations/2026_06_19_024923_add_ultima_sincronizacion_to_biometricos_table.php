<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('biometricos', function (Blueprint $table) {
            $table->timestamp('ultima_sinc_usuarios')->nullable()->after('puerto');
            $table->timestamp('ultima_sinc_registros')->nullable()->after('ultima_sinc_usuarios');
        });
    }
    public function down(): void {
        Schema::table('biometricos', function (Blueprint $table) {
            $table->dropColumn(['ultima_sinc_usuarios', 'ultima_sinc_registros']);
        });
    }
};