<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_config_pallets', function (Blueprint $table) {
            $table->dropUnique(['cajas_x_pallet']);
        });
    }

    public function down(): void
    {
        Schema::table('wms_config_pallets', function (Blueprint $table) {
            $table->unique('cajas_x_pallet');
        });
    }
};