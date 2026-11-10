<?php

namespace Database\Seeders;

use App\Models\IndicadoresProcesador;
use Illuminate\Database\Seeder;

class IndicadoresProcesadoresSeeder extends Seeder
{
    public function run(): void
    {
        // Fechas expresadas como primer mes del trimestre publicado por Intel ARK.
        // No se inventa el día exacto: precision_fecha conserva el nivel de precisión.
        $intelArk = 'https://www.intel.la/content/www/xl/es/ark.html';
        $rows = [
            ['i3-2100', '2011-01-01', 'trimestre'],
            ['i3-2120', '2011-01-01', 'trimestre'],
            ['i3-2130', '2011-07-01', 'trimestre'],
            ['i3-3220', '2012-07-01', 'trimestre'],
            ['i3-3240', '2012-07-01', 'trimestre'],
            ['i3-3250', '2013-04-01', 'trimestre'],
            ['i3-4130', '2013-07-01', 'trimestre'],
            ['i3-4130T', '2013-07-01', 'trimestre'],
            ['i3-4150', '2014-04-01', 'trimestre'],
            ['i3-4160', '2014-07-01', 'trimestre'],
            ['i3-4170', '2015-01-01', 'trimestre'],
            ['i3-6100', '2015-07-01', 'trimestre'],
            ['i3-7100', '2017-01-01', 'trimestre'],
            ['i3-8100', '2018-01-01', 'trimestre'],
            ['i3-9100F', '2019-04-01', 'trimestre'],
            ['i5-3330', '2012-07-01', 'trimestre'],
            ['i5-4200M', '2013-07-01', 'trimestre'],
            ['i5-4200U', '2013-07-01', 'trimestre'],
            ['i5-4440', '2013-07-01', 'trimestre'],
            ['i5-4460', '2014-04-01', 'trimestre'],
            ['i5-5200U', '2015-01-01', 'trimestre'],
            ['i5-6200U', '2015-07-01', 'trimestre'],
            ['i5-6400', '2015-07-01', 'trimestre'],
            ['i5-8250U', '2017-07-01', 'trimestre'],
            ['i5-8500', '2018-01-01', 'trimestre'],
            ['i5-1035G1', '2019-07-01', 'trimestre'],
            ['i5-10400', '2020-04-01', 'trimestre'],
            ['i5-10400F', '2020-04-01', 'trimestre'],
            ['i5-1135G7', '2020-07-01', 'trimestre'],
            ['i5-11400', '2021-01-01', 'trimestre'],
            ['i5-11400H', '2021-01-01', 'trimestre'],
            ['i5-1235U', '2022-01-01', 'trimestre'],
            ['i5-12400', '2022-01-01', 'trimestre'],
            ['i5-1334U', '2023-01-01', 'trimestre'],
            ['i5-1335U', '2023-01-01', 'trimestre'],
            ['i5-13400', '2023-01-01', 'trimestre'],
            ['i5-13420H', '2023-07-01', 'trimestre'],
            ['i5-14400', '2024-01-01', 'trimestre'],
            ['i7-10750H', '2020-04-01', 'trimestre'],
            ['i7-11390H', '2021-04-01', 'trimestre'],
            ['i7-12650H', '2022-01-01', 'trimestre'],
            ['i7-1355U', '2023-01-01', 'trimestre'],
            ['i7-13620H', '2023-01-01', 'trimestre'],
            ['i7-14700T', '2024-01-01', 'trimestre'],
            ['i9-12900K', '2021-10-01', 'trimestre'],
            ['i9-13900H', '2023-01-01', 'trimestre'],
            ['i9-13900K', '2022-10-01', 'trimestre'],
            ['Core Ultra 9 185H', '2023-10-01', 'trimestre'],
        ];

        foreach ($rows as [$model, $launch, $precision]) {
            IndicadoresProcesador::updateOrCreate(
                ['modelo_normalizado' => mb_strtolower($model)],
                [
                    'fabricante' => 'Intel',
                    'modelo' => $model,
                    'fecha_lanzamiento' => $launch,
                    'precision_fecha' => $precision,
                    'fuente_url' => $intelArk,
                    'verificado_en' => now()->toDateString(),
                    'activo' => true,
                ]
            );
        }
    }
}
