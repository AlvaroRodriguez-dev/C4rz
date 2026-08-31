<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BioAsistenciaImportada extends Model
{
    protected $table = 'bio_asistencia_importada';
    protected $fillable = [
        'biometrico_id', 'user_id', 'timestamp',
        'device_id', 'state', 'verify_method',
        'work_code', 'archivo_origen',
    ];

    public function biometrico()
    {
        return $this->belongsTo(Biometrico::class, 'biometrico_id');
    }

    public function usuario()
    {
        return $this->belongsTo(BioUsuario::class, 'user_id', 'user_id')
                    ->where('biometrico_id', $this->biometrico_id);
    }
}