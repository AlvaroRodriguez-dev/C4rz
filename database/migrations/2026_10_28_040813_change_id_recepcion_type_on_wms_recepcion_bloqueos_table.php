<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_recepcion_bloqueos', function (Blueprint $table) {
            $table->string('id_recepcion', 20)->change();
        });
    }

    public function down(): void
    {
        // No convertir a entero: los códigos de recepción son alfanuméricos.
        // La migración inversa se omite intencionalmente para evitar
        // pérdida de información.
    }
};