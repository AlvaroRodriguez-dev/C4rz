<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('rrhh_agencias', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('nombre', 150);
            $table->decimal('latitud', 10, 7);
            $table->decimal('longitud', 10, 7);
            $table->integer('tolerancia')->default(100)
                  ->comment('Radio de tolerancia en metros');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void {
        Schema::dropIfExists('rrhh_agencias');
    }
};