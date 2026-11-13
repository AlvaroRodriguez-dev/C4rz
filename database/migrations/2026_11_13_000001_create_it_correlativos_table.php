<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('it_correlativos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 30);
            $table->unsignedSmallInteger('anio');
            $table->unsignedBigInteger('ultimo_numero')->default(0);
            $table->timestamps();
            $table->unique(['tipo', 'anio'], 'it_correlativos_tipo_anio_uq');
        });

        // Continuar desde el último número ya emitido para no colisionar al desplegar.
        $maximos = [];
        DB::table('it_solicitudes')
            ->where('numero', 'like', 'SOL-TI-%')
            ->pluck('numero')
            ->each(function ($numero) use (&$maximos) {
                if (preg_match('/^SOL-TI-(\d{4})-(\d{6})$/', $numero, $coincidencias)) {
                    $anio = (int) $coincidencias[1];
                    $correlativo = (int) $coincidencias[2];
                    $maximos[$anio] = max($maximos[$anio] ?? 0, $correlativo);
                }
            });

        foreach ($maximos as $anio => $ultimoNumero) {
            DB::table('it_correlativos')->insert([
                'tipo' => 'SOL-TI',
                'anio' => $anio,
                'ultimo_numero' => $ultimoNumero,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('it_correlativos');
    }
};
