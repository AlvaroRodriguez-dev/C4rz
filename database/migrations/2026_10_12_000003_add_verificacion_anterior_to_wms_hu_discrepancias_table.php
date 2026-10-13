<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_hu_discrepancias', function (Blueprint $table) {
            $table->longText('verificacion_anterior')->nullable()->after('valor_anterior');
        });
    }

    public function down(): void
    {
        Schema::table('wms_hu_discrepancias', function (Blueprint $table) {
            $table->dropColumn('verificacion_anterior');
        });
    }
};
