<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('bio_novedades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('biometrico_id')->nullable(); // null = todos
            $table->string('user_id', 20);
            $table->date('fecha');
            $table->string('ticket_id', 100)->nullable();
            $table->timestamps();

            $table->foreign('biometrico_id')
                  ->references('id')->on('biometricos')
                  ->onDelete('set null');

            $table->unique(['user_id', 'fecha'], 'bio_novedades_unique');
        });
    }

    public function down(): void {
        Schema::dropIfExists('bio_novedades');
    }
};