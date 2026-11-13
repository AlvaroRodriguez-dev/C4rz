<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wms_stock_locks')) {
            return;
        }

        Schema::create('wms_stock_locks', function (Blueprint $table): void {
            $table->id();
            $table->string('pallet', 30)->unique('wms_stock_locks_pallet_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('wms_stock_locks')) {
            Schema::drop('wms_stock_locks');
        }
    }
};
