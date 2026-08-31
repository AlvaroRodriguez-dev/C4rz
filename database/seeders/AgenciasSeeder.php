<?php

namespace Database\Seeders;

use App\Models\Agencia;
use Illuminate\Database\Seeder;

class AgenciasSeeder extends Seeder
{
    /**
     * Datos tomados de https://www.faboce.com.bo/contact
     * (Puntos de venta y showrooms a nivel nacional, con enlaces reales de Google Maps)
     */
    public function run(): void
    {
        $agencias = [
            // Cochabamba
            ['codigo' => 'CBB-NORTE', 'descripcion' => 'Agencia Norte', 'ciudad' => 'Cochabamba', 'direccion' => 'Av. Beijing y Av. América', 'url_maps' => 'https://maps.app.goo.gl/tt7N1ZD1dAdDrLi7A'],
            ['codigo' => 'CBB-PETROLERA', 'descripcion' => 'Agencia Petrolera', 'ciudad' => 'Cochabamba', 'direccion' => 'Av. Petrolera km. 1', 'url_maps' => 'https://www.google.com/maps/search/-17.41991,+-66.15253'],
            ['codigo' => 'CBB-ESTE', 'descripcion' => 'Agencia Este', 'ciudad' => 'Cochabamba', 'direccion' => 'Av. Gral. Galindo N° 1459', 'url_maps' => 'https://maps.app.goo.gl/UQLdXh2FsSE82oRH8'],
            ['codigo' => 'CBB-SHOWROOM', 'descripcion' => 'Showroom Fábrica Cochabamba', 'ciudad' => 'Cochabamba', 'direccion' => 'Carretera a Sacaba Km. 8,5', 'url_maps' => 'https://www.google.com/maps/place/17%C2%B023\'34.9%22S+66%C2%B004\'16.9%22W/@-17.39302,-66.07135,17z'],

            // Santa Cruz
            ['codigo' => 'SCZ-BANZER', 'descripcion' => 'Agencia Banzer', 'ciudad' => 'Santa Cruz', 'direccion' => 'Av. Banzer, casi 4to anillo', 'url_maps' => 'https://maps.app.goo.gl/4jtY4M6bmKaa1GfF7'],
            ['codigo' => 'SCZ-GRIGOTA', 'descripcion' => 'Agencia Grigotá', 'ciudad' => 'Santa Cruz', 'direccion' => 'Av. Doble Vía La Guardia #3865', 'url_maps' => 'https://maps.app.goo.gl/tvLTSX4DvQ1u9PKLA'],
            ['codigo' => 'SCZ-COTOCA', 'descripcion' => 'Agencia Cotoca', 'ciudad' => 'Santa Cruz', 'direccion' => 'Av. Virgen de Cotoca y 4to anillo', 'url_maps' => 'https://maps.app.goo.gl/dRXLQJNVDNmKABBp6'],
            ['codigo' => 'SCZ-MONTERO', 'descripcion' => 'Agencia Montero', 'ciudad' => 'Santa Cruz', 'direccion' => 'Carretera a Montero casi 2do Anillo', 'url_maps' => 'https://maps.app.goo.gl/uuVieyDwEa87vSye6'],
            ['codigo' => 'SCZ-WARNES', 'descripcion' => 'Agencia Warnes', 'ciudad' => 'Santa Cruz', 'direccion' => 'Carretera al Norte (Warnes) Km 29, entrada a "El Cairo"', 'url_maps' => 'https://maps.app.goo.gl/VnSQZ4pxGYsiChdn7'],
            ['codigo' => 'SCZ-SHOWROOM', 'descripcion' => 'Showroom Fábrica Santa Cruz', 'ciudad' => 'Santa Cruz', 'direccion' => 'Carretera a Cotoca Km. 13,5', 'url_maps' => 'https://maps.app.goo.gl/EMBAKyQ9oBNrySem8'],
            ['codigo' => 'SCZ-MONTES', 'descripcion' => 'Agencia Montes', 'ciudad' => 'Santa Cruz', 'direccion' => 'Av. Montes #710, Edif. Ursic Ltda.', 'url_maps' => 'https://maps.app.goo.gl/ijSmGJLXCec1jpCy8'],
            ['codigo' => 'SCZ-COTACOTA', 'descripcion' => 'Agencia Cota Cota', 'ciudad' => 'Santa Cruz', 'direccion' => 'C. J. Muñoz Reyes #2485', 'url_maps' => 'https://maps.app.goo.gl/U126giqozp6vukur9'],
            ['codigo' => 'SCZ-RIOSECO', 'descripcion' => 'Agencia Río Seco', 'ciudad' => 'Santa Cruz', 'direccion' => 'Av. Juan Pablo II #100', 'url_maps' => 'https://maps.app.goo.gl/4dM4ATaCsSYJh859A'],

            // La Paz
            ['codigo' => 'LPZ-OUTLET', 'descripcion' => 'Outlet Faboce', 'ciudad' => 'La Paz', 'direccion' => 'Av. 6 de Marzo, una cuadra antes del Puente Bolivia', 'url_maps' => 'https://www.google.com/maps/place/FR4F+XV2,+La+Paz/@-16.5426437,-68.1752338,17z'],

            // Sucre
            ['codigo' => 'SUC-SANMARCOS', 'descripcion' => 'Agencia San Marcos', 'ciudad' => 'Sucre', 'direccion' => 'Calle Pando casi Circunvalación', 'url_maps' => 'https://maps.app.goo.gl/MeqiBcnh8kSRX2ew5'],
            ['codigo' => 'SUC-SUCRE', 'descripcion' => 'Agencia Sucre', 'ciudad' => 'Sucre', 'direccion' => 'Calle J. Prudencio Bustillo, a ½ cuadra de la terminal de buses', 'url_maps' => 'https://maps.app.goo.gl/C28UZ45D2HkRNKkL8'],

            // Oruro
            ['codigo' => 'ORU-ORURO', 'descripcion' => 'Agencia Oruro', 'ciudad' => 'Oruro', 'direccion' => 'Calle Pagador esquina calle Caro #1090', 'url_maps' => 'https://www.google.com/maps/place/17%C2%B058\'01.0%22S+67%C2%B006\'34.5%22W/@-17.9669543,-67.1121452,17z'],

            // Potosí
            ['codigo' => 'PTS-POTOSI', 'descripcion' => 'Agencia Potosí', 'ciudad' => 'Potosí', 'direccion' => 'Av. El Prado San Clemente, entre c. Boquerón y c. 07 de Agosto', 'url_maps' => 'https://www.google.com/maps/place/19%C2%B034\'22.4%22S+65%C2%B045\'26.7%22W/@-19.5731423,-65.7577901,19.7z'],

            // Trinidad (Beni)
            ['codigo' => 'TRI-TRINIDAD', 'descripcion' => 'Agencia Trinidad', 'ciudad' => 'Trinidad', 'direccion' => 'Av. Adolfo Velasco Avila, Zona Industrial', 'url_maps' => 'https://maps.app.goo.gl/8ub2g646Vh47T6jR9'],
        ];

        foreach ($agencias as $agencia) {
            Agencia::updateOrCreate(['codigo' => $agencia['codigo']], $agencia);
        }
    }
}
