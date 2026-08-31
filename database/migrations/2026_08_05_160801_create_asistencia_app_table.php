<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('asistencia_app', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('license', 50);
            $table->string('name', 150);
            $table->string('lastname', 150);
            $table->enum('tipo', ['INGRESO', 'SALIDA']);
            $table->string('foto', 255);
            $table->dateTime('fecha_servidor');
            $table->dateTime('fecha_cliente')->nullable();
            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
    public function down(): void {
        Schema::dropIfExists('asistencia_app');
    }
};