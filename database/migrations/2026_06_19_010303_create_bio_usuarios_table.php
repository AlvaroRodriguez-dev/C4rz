<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('bio_usuarios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('biometrico_id');
            $table->integer('uid');          // número de serie interno
            $table->string('user_id', 20);   // ID del usuario en el dispositivo
            $table->string('name', 100)->nullable();
            $table->tinyInteger('role')->default(0); // 0=User, 14=SuperAdmin
            $table->string('password', 20)->nullable();
            $table->string('card_no', 20)->nullable();
            $table->timestamps();

            $table->foreign('biometrico_id')->references('id')->on('biometricos')->onDelete('cascade');
            $table->unique(['biometrico_id', 'uid']);
        });
    }
    public function down(): void { Schema::dropIfExists('bio_usuarios'); }
};