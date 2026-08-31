<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RrhhAgencia extends Model
{
    use SoftDeletes;

    protected $table    = 'rrhh_agencias';        // ← nombre de tabla
    protected $fillable = [
        'codigo', 'nombre', 'latitud', 'longitud', 'tolerancia', 'activo',
    ];

    protected $casts = [
        'activo'     => 'boolean',
        'latitud'    => 'float',
        'longitud'   => 'float',
        'tolerancia' => 'integer',
    ];

    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'rrhh_agencia_user',   
            'agencia_id',
            'user_id'
        );
    }

    public function distanciaMetros(float $lat, float $lng): float
    {
        $radioTierra = 6371000;
        $dLat = deg2rad($lat - $this->latitud);
        $dLng = deg2rad($lng - $this->longitud);
        $a    = sin($dLat / 2) * sin($dLat / 2) +
                cos(deg2rad($this->latitud)) * cos(deg2rad($lat)) *
                sin($dLng / 2) * sin($dLng / 2);
        return $radioTierra * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function dentroDeRango(float $lat, float $lng): bool
    {
        return $this->distanciaMetros($lat, $lng) <= $this->tolerancia;
    }
}