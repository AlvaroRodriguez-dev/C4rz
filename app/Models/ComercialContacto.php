<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ComercialContacto extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nombre', 'cargo', 'telefono', 'email', 'foto', 'agencia_id', 'activo',
    ];

    protected $casts = ['activo' => 'boolean'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($m) {
            $m->uuid = $m->uuid ?? (string) Str::uuid();
            $m->created_by = auth()->id();
        });
        static::updating(fn ($m) => $m->updated_by = auth()->id());
        static::deleting(function ($m) {
            if (! $m->isForceDeleting()) {
                $m->deleted_by = auth()->id();
                $m->saveQuietly();
            }
        });
    }

    public function agencia()
    {
        return $this->belongsTo(Agencia::class);
    }

    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto ? asset('uploads/comercial/'.$this->foto) : null;
    }

    public function getUrlPublicaAttribute(): string
    {
        return route('tarjeta.show', $this->uuid);
    }

    public function getWhatsappUrlAttribute(): string
    {
        $digits = preg_replace('/\D/', '', $this->telefono);

        // Si el número ya trae código de país (más de 8 dígitos), se usa tal cual.
        // Los celulares en Bolivia son de 8 dígitos, así que si tiene 8 o menos,
        // se le antepone el 591.
        if (strlen($digits) <= 8) {
            $digits = '591'.$digits;
        }

        return "https://wa.me/{$digits}";
    }
}
