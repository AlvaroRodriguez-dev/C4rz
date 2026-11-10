<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('indicadores_ocs_equipos', function(Blueprint $t) {
  $t->id(); $t->foreignId('importacion_id')->constrained('indicadores_ocs_importaciones')->cascadeOnDelete();
  $t->string('tag')->nullable(); $t->string('dispositivo')->nullable(); $t->string('id_dispositivo')->nullable();
  $t->string('sistema_operativo')->nullable(); $t->unsignedInteger('ram_mb')->nullable();
  $t->string('tipo_cpu')->nullable(); $t->string('propietario')->nullable();
  $t->date('ultimo_inventario')->nullable(); $t->date('fecha_compra_estimada')->nullable();
  $t->string('tipo_equipo', 30); $t->string('estado_soporte', 30)->nullable();
  $t->unsignedSmallInteger('antiguedad_meses')->nullable(); $t->boolean('ram_hasta_4gb')->default(false);
  $t->boolean('antiguedad_mayor_5')->default(false); $t->timestamps();
  $t->index(['importacion_id','estado_soporte']); $t->index(['importacion_id','tipo_equipo']);
 }); }
 public function down(): void { Schema::dropIfExists('indicadores_ocs_equipos'); }
};