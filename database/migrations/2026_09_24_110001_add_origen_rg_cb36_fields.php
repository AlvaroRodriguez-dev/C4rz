<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::table('wms_entregas_produccion', function (Blueprint $table) { $table->string('planta',100)->nullable()->after('almacen_id'); $table->string('formato',20)->nullable()->after('planta'); }); }
 public function down(): void { Schema::table('wms_entregas_produccion', function (Blueprint $table) { $table->dropColumn(['planta','formato']); }); }
};