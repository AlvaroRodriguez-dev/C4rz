<?php

namespace App\Services\Indicadores;

use App\Models\IndicadoresProcesador;
use Carbon\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

class OcsInventoryService
{
    public function process(string $path, int $year, int $month): array
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            throw new RuntimeException('No se pudo leer el archivo.');
        }

        $first = fgets($handle);
        if ($first === false) {
            fclose($handle);
            throw new RuntimeException('El archivo está vacío.');
        }

        $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
        rewind($handle);
        $headers = fgetcsv($handle, 0, $delimiter);
        $headers = array_map(fn ($value) => $this->normalize((string) $value), $headers ?: []);
        $map = [];
        foreach ($headers as $index => $name) {
            $map[$name] = $index;
        }

        $get = function (array $row, array $names) use ($map): string {
            foreach ($names as $name) {
                $key = $this->normalize($name);
                if (isset($map[$key])) {
                    return trim((string) ($row[$map[$key]] ?? ''));
                }
            }
            return '';
        };

        $rows = [];
        $total = $pcs = $unsupported = $pending = $ram = $old = $missing = 0;
        $periodDate = Carbon::create($year, $month, 1)->startOfMonth();

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count($row) === 1 && trim((string) $row[0]) === '') {
                continue;
            }

            $total++;
            $os = $get($row, ['Sistema Operativo', 'Sistema operativo']);
            $type = $this->deviceType($os);
            $ramRaw = str_replace(',', '.', $get($row, ['RAM (MB)', 'RAM']));
            $ramMb = is_numeric($ramRaw) ? (int) round((float) $ramRaw) : null;
            $support = $this->supportStatus($os);

            $cpu = $get($row, ['Tipo de CPU']);
            $processor = $this->findProcessor($cpu);
            $providedPurchaseDate = $this->parseDate($get($row, [
                'Fecha aproximada de compra',
                'Fecha de compra estimada',
            ]));

            $purchaseDate = $providedPurchaseDate;
            $purchaseSource = $providedPurchaseDate ? 'fecha_informada' : null;

            if (!$purchaseDate && $processor) {
                $launchDate = $processor->fecha_lanzamiento->copy()->startOfMonth();
                // Regla acordada: equipos cuyo procesador se lanzó en el año calendario actual
                // conservan ese mismo año; para lanzamientos anteriores se suma un año.
                $purchaseDate = $launchDate->year === now()->year
                    ? $launchDate->toDateString()
                    : $launchDate->copy()->addMonths(12)->toDateString();
                $purchaseSource = 'estimada_procesador_12m';
            }

            $ageMonths = $purchaseDate
                ? max(0, Carbon::parse($purchaseDate)->startOfMonth()->diffInMonths($periodDate, false))
                : null;
            $isOld = $ageMonths !== null && $ageMonths > 60;

            if ($type === 'computadora') {
                $pcs++;
                if ($support === 'sin_soporte') $unsupported++;
                if ($support === 'pendiente') $pending++;
                if ($ramMb !== null && $ramMb <= 4096) $ram++;
                if ($purchaseDate === null) $missing++;
                if ($isOld) $old++;
            }

            $rows[] = [
                'tag' => $get($row, ['Información de cuenta: TAG', 'TAG']),
                'dispositivo' => $get($row, ['Dispositivo']),
                'id_dispositivo' => $get($row, ['ID Dispositivo']),
                'sistema_operativo' => $os,
                'ram_mb' => $ramMb,
                'tipo_cpu' => $cpu,
                'procesador_id' => $processor?->id,
                'propietario' => $get($row, ['Propietario']),
                'ultimo_inventario' => $this->parseDate($get($row, ['Último inventario', 'Ultimo inventario'])),
                'fecha_compra_estimada' => $purchaseDate,
                'origen_fecha_compra' => $purchaseSource,
                'tipo_equipo' => $type,
                'estado_soporte' => $type === 'computadora' ? $support : null,
                'antiguedad_meses' => $ageMonths,
                'ram_hasta_4gb' => $type === 'computadora' && $ramMb !== null && $ramMb <= 4096,
                'antiguedad_mayor_5' => $type === 'computadora' && $isOld,
            ];
        }

        fclose($handle);

        if ($total === 0) {
            throw new RuntimeException('El archivo no contiene registros.');
        }
        if (!isset($map[$this->normalize('Dispositivo')]) || !isset($map[$this->normalize('Sistema Operativo')])) {
            throw new RuntimeException('El CSV no contiene las columnas Dispositivo y Sistema Operativo.');
        }

        $percentage = fn ($value) => $pcs ? round($value / $pcs * 100, 2) : 0;

        return [
            'rows' => $rows,
            'total_registros' => $total,
            'total_computadoras' => $pcs,
            'so_sin_soporte' => $unsupported,
            'so_pendiente_verificacion' => $pending,
            'ram_hasta_4gb' => $ram,
            'antiguedad_mayor_5' => $old,
            'sin_fecha_compra' => $missing,
            'porcentaje_so_sin_soporte' => $percentage($unsupported),
            'porcentaje_ram_hasta_4gb' => $percentage($ram),
            'porcentaje_antiguedad_mayor_5' => $percentage($old),
        ];
    }

    private function findProcessor(string $cpu): ?IndicadoresProcesador
    {
        $normalized = Str::lower(Str::ascii($cpu));
        $normalized = preg_replace('/\[[^\]]*\]/', ' ', $normalized);
        $normalized = preg_replace('/\b(intel|amd|r)\b/', ' ', $normalized);
        $normalized = preg_replace('/\(r\)|\(tm\)|\(c\)/', ' ', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        $catalog = IndicadoresProcesador::query()->where('activo', true)->get();
        foreach ($catalog as $processor) {
            $needle = Str::lower($processor->modelo_normalizado);
            if (preg_match('/(?<![a-z0-9])' . preg_quote($needle, '/') . '(?![a-z0-9])/i', $normalized)) {
                return $processor;
            }
        }

        return null;
    }

    private function deviceType(string $os): string
    {
        $value = Str::lower($os);
        if (Str::contains($value, 'android')) return 'movil';
        if (Str::contains($value, ['windows server', 'linux server', 'ubuntu server', 'debian server'])) return 'servidor';
        if (Str::contains($value, ['windows', 'macos', 'mac os', 'ubuntu', 'debian', 'linux', 'chrome os'])) return 'computadora';
        return 'no_clasificado';
    }

    private function normalize(string $value): string
    {
        $value = Str::ascii(mb_strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', $value))));
        return preg_replace('/\s+/', ' ', $value);
    }

    private function parseDate(string $value): ?string
    {
        if ($value === '') return null;
        $months = [
            'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
            'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,
            'septiembre' => 9, 'setiembre' => 9, 'octubre' => 10,
            'noviembre' => 11, 'diciembre' => 12,
        ];
        if (preg_match('/^([a-záéíóú]+)\s+(\d{4})$/iu', trim($value), $match)) {
            $key = Str::lower(Str::ascii($match[1]));
            if (isset($months[$key])) return Carbon::create((int) $match[2], $months[$key], 1)->toDateString();
        }
        foreach (['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'Y-m-d H:i:s', 'd/m/Y H:i:s'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->toDateString();
            } catch (\Throwable) {
            }
        }
        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function supportStatus(string $os): string
    {
        $value = Str::lower($os);
        if ($value === '') return 'pendiente';
        if (preg_match('/windows xp|windows 7|windows 8(?:\s|$)|windows 8\.1|server 2008|server 2012/', $value)) return 'sin_soporte';
        if (Str::contains($value, 'windows 10') && !Str::contains($value, 'ltsc')) return 'sin_soporte';
        return 'pendiente';
    }
}
