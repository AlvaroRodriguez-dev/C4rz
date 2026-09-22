<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_tipo_documentos', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('codigo', 2)->unique();
            $table->string('descripcion');
            $table->string('talonario', 1)->default('0');
            $table->unsignedBigInteger('created_id')->default(0);
            $table->unsignedBigInteger('updated_id')->default(0);
            $table->unsignedBigInteger('deleted_id')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['codigo', 'talonario']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_tipo_documentos');
    }
};