<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rh_configuraciones_laborales', function (Blueprint $table) {
            $table->unsignedBigInteger('area_id_externo')
                ->nullable()
                ->after('fecha_fin');

            $table->string('area_codigo', 50)
                ->nullable()
                ->after('area_id_externo');

            $table->string('area_nombre', 150)
                ->nullable()
                ->after('area_codigo');

            $table->unsignedBigInteger('seccion_id_externo')
                ->nullable()
                ->after('area_nombre');

            $table->string('seccion_nombre', 150)
                ->nullable()
                ->after('seccion_id_externo');

            $table->unsignedBigInteger('cargo_id_externo')
                ->nullable()
                ->after('seccion_nombre');

            $table->string('cargo_nombre', 150)
                ->nullable()
                ->after('cargo_id_externo');

            $table->unsignedBigInteger('jerarquia_id_externo')
                ->nullable()
                ->after('cargo_nombre');

            $table->string('jerarquia_nombre', 100)
                ->nullable()
                ->after('jerarquia_id_externo');

            $table->string('agencia_codigo', 20)
                ->nullable()
                ->after('jerarquia_nombre');

            $table->string('agencia_nombre', 150)
                ->nullable()
                ->after('agencia_codigo');

            $table->string('ciudad', 100)
                ->nullable()
                ->after('agencia_nombre');

            $table->index('area_id_externo', 'rh_lab_area_ext_idx');
            $table->index('seccion_id_externo', 'rh_lab_seccion_ext_idx');
            $table->index('cargo_id_externo', 'rh_lab_cargo_ext_idx');
            $table->index('jerarquia_id_externo', 'rh_lab_jerarquia_ext_idx');
            $table->index('agencia_codigo', 'rh_lab_agencia_idx');
        });
    }

    public function down(): void
    {
        Schema::table('rh_configuraciones_laborales', function (Blueprint $table) {
            $table->dropIndex('rh_lab_area_ext_idx');
            $table->dropIndex('rh_lab_seccion_ext_idx');
            $table->dropIndex('rh_lab_cargo_ext_idx');
            $table->dropIndex('rh_lab_jerarquia_ext_idx');
            $table->dropIndex('rh_lab_agencia_idx');

            $table->dropColumn([
                'area_id_externo',
                'area_codigo',
                'area_nombre',
                'seccion_id_externo',
                'seccion_nombre',
                'cargo_id_externo',
                'cargo_nombre',
                'jerarquia_id_externo',
                'jerarquia_nombre',
                'agencia_codigo',
                'agencia_nombre',
                'ciudad',
            ]);
        });
    }
};
