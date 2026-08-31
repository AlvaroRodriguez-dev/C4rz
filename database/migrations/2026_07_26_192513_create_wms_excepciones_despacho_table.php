<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_excepciones_despacho', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('update_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('delete_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('tipo_registro', 5);
            $table->string('id_registro', 20);
            $table->string('codigo', 30);
            $table->string('descrip', 60)->nullable();
            $table->string('descrip1', 60)->nullable();
            $table->string('lote_solicitado', 30)->nullable();
            $table->string('lote_aplicado', 30);
            $table->unsignedInteger('cantidad');
            $table->string('motivo', 200)->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['tipo_registro', 'id_registro']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_excepciones_despacho');
    }
};