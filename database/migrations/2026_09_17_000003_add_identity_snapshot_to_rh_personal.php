<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rh_personal', function (Blueprint $table) {
            $table->string('nombre', 150)->nullable()->after('license');
            $table->string('apellido', 200)->nullable()->after('nombre');
        });
    }

    public function down(): void
    {
        Schema::table('rh_personal', function (Blueprint $table) {
            $table->dropColumn(['nombre', 'apellido']);
        });
    }
};
