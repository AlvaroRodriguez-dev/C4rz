<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('biometricos', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 20);
            $table->string('agencia', 100);
            $table->string('descripcion', 150);
            $table->string('detalle', 255)->nullable();
            $table->integer('puerto')->default(4370);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('biometricos'); }
};