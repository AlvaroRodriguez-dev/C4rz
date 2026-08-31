<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('comercial_contactos', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('nombre');
            $table->string('cargo'); // ej: EJECUTIVO DE VENTAS EMPRESARIALES
            $table->string('telefono');
            $table->string('email');
            $table->string('foto')->nullable(); // path en storage/public
            $table->foreignId('agencia_id')->constrained('agencias');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comercial_contactos');
    }
};
