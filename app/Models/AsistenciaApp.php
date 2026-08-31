<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsistenciaApp extends Model
{
    protected $table = 'asistencia_app';
    protected $fillable = [
        'user_id', 'license', 'name', 'lastname', 'tipo',
        'foto', 'fecha_servidor', 'fecha_cliente',
        'latitud', 'longitud', 'direccion',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}