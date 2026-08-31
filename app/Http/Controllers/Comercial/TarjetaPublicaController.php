<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\ComercialContacto;
use Illuminate\Support\Str;

class TarjetaPublicaController extends Controller
{
    public function show($uuid)
    {
        $contacto = ComercialContacto::with('agencia')
            ->where('uuid', $uuid)
            ->where('activo', true)
            ->firstOrFail();

        return view('comercial.tarjeta-publica', compact('contacto'));
    }

    public function vcard($uuid)
    {
        $contacto = ComercialContacto::with('agencia')
            ->where('uuid', $uuid)
            ->where('activo', true)
            ->firstOrFail();

        $website = config('comercial.website');
        $empresa = config('comercial.empresa');

        $vcard = "BEGIN:VCARD\r\n";
        $vcard .= "VERSION:3.0\r\n";
        $vcard .= "N:{$contacto->nombre};;;;\r\n";
        $vcard .= "FN:{$contacto->nombre}\r\n";
        $vcard .= "ORG:{$empresa}\r\n";
        $vcard .= "TITLE:{$contacto->cargo}\r\n";
        $vcard .= "TEL;TYPE=WORK,VOICE:{$contacto->telefono}\r\n";
        $vcard .= "EMAIL:{$contacto->email}\r\n";
        if ($contacto->agencia) {
            $vcard .= "ADR;TYPE=WORK:;;{$contacto->agencia->direccion};{$contacto->agencia->ciudad};;;Bolivia\r\n";
        }
        $vcard .= "URL:{$website}\r\n";
        $vcard .= "END:VCARD\r\n";

        return response($vcard, 200, [
            'Content-Type' => 'text/vcard; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.Str::slug($contacto->nombre).'.vcf"',
        ]);
    }
}
