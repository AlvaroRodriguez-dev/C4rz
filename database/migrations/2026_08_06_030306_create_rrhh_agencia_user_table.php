<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('rrhh_agencia_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agencia_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('agencia_id')->references('id')->on('rrhh_agencias')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['agencia_id', 'user_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('agencia_user');
    }
};