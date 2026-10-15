<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_ingresos', function (Blueprint $table) {
            $table->string('doc_origen', 50)->nullable()->after('rdocum')
                ->comment('Código del documento que origina el ingreso físico, por ejemplo liberación o transferencia entre almacenes');
        });
    }

    public function down(): void
    {
        Schema::table('wms_ingresos', function (Blueprint $table) {
            $table->dropColumn('doc_origen');
        });
    }
};
