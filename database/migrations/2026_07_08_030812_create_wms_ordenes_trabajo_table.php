<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_ordenes_trabajo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('update_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('delete_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('tipo_registro', 5);
            $table->string('id_registro', 20);
            $table->string('glosa', 150)->nullable();
            $table->enum('estado', ['pendiente', 'completada'])->default('pendiente');
            $table->timestamp('completada_at')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index('id_registro');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_ordenes_trabajo');
    }
};