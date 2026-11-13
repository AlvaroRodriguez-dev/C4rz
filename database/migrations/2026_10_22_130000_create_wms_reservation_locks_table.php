<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wms_reservation_locks')) {
            return;
        }

        Schema::create('wms_reservation_locks', function (Blueprint $table): void {
            $table->id();
            $table->string('tipo_registro', 20);
            $table->string('id_registro', 50);
            $table->timestamps();
            $table->unique(
                ['tipo_registro', 'id_registro'],
                'wms_reservation_locks_note_unique'
            );
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('wms_reservation_locks')) {
            Schema::drop('wms_reservation_locks');
        }
    }
};
