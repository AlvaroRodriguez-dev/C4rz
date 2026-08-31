<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('bio_asistencia', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('biometrico_id');
            $table->integer('uid');           // número de serie del registro
            $table->string('user_id', 20);    // ID del usuario
            $table->tinyInteger('state')->nullable(); // 1=Huella, 4=RF Card
            $table->dateTime('timestamp');    // fecha/hora del marcaje
            $table->smallInteger('type')->nullable(); // tipo: entrada, salida, etc.
            $table->timestamps();

            $table->foreign('biometrico_id')->references('id')->on('biometricos')->onDelete('cascade');
            $table->unique(['biometrico_id', 'uid'], 'bio_asistencia_unique');
        });
    }
    public function down(): void { Schema::dropIfExists('bio_asistencia'); }
};