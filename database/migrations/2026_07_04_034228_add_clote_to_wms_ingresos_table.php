<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_ingresos', function (Blueprint $table) {
            $table->string('clote', 30)->nullable()->after('codigo');
        });
    }

    public function down(): void
    {
        Schema::table('wms_ingresos', function (Blueprint $table) {
            $table->dropColumn('clote');
        });
    }
};