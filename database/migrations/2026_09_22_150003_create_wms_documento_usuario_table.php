<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_documento_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_documento')
                ->constrained('wms_documentos', 'id')
                ->cascadeOnDelete();
            $table->foreignId('id_usuario')
                ->constrained('users', 'id')
                ->restrictOnDelete();

            $table->unsignedBigInteger('created_id')->default(0);
            $table->unsignedBigInteger('updated_id')->default(0);
            $table->unsignedBigInteger('deleted_id')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['id_documento', 'id_usuario']);
            $table->index(['id_usuario', 'id_documento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_documento_usuario');
    }
};