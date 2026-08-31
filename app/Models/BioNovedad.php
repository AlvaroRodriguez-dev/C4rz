<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BioNovedad extends Model
{
    protected $table    = 'bio_novedades';
    protected $fillable = ['biometrico_id', 'user_id', 'fecha', 'ticket_id'];

    protected $casts = ['fecha' => 'date'];

    public function biometrico()
    {
        return $this->belongsTo(Biometrico::class, 'biometrico_id');
    }
}