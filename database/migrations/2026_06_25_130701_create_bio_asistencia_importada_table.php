<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('bio_asistencia_importada', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('biometrico_id');
            $table->string('user_id', 20);        // col 1: ID usuario
            $table->dateTime('timestamp');         // col 2: fecha y hora
            $table->tinyInteger('device_id')->default(1);  // col 3: ID dispositivo
            $table->tinyInteger('state')->nullable();       // col 4: estado asistencia
            $table->tinyInteger('verify_method')->nullable(); // col 5: método verificación
            $table->tinyInteger('work_code')->default(0);  // col 6: código trabajo
            $table->string('archivo_origen', 255)->nullable(); // nombre del archivo importado
            $table->timestamps();

            $table->foreign('biometrico_id')->references('id')->on('biometricos')->onDelete('cascade');
            // Evitar duplicados: mismo usuario, misma fecha/hora, mismo biométrico
            $table->unique(['biometrico_id', 'user_id', 'timestamp'], 'bio_imp_unique');
        });
    }
    public function down(): void {
        Schema::dropIfExists('bio_asistencia_importada');
    }
};