<?php

namespace App\\Http\\Controllers\\Wms;

use App\\Http\\Controllers\\Controller;
use App\\Models\\WmsIngreso;
use App\\Services\\WmsContextService;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Validation\\Rule;

class WmsIngresoVerController extends Controller
{
    public function __construct(private WmsContextService $context)
    {
    }

    public function index()
    {
        $almacenes = $this->context->almacenes();

        return view('wms.ingresos.ver', compact('almacenes'));
    }

    /**
     * AJAX: listado paginado limitado a un almacén activo asignado al usuario.
     */
    public function buscar(Request $request)
    {
        $almacenes = $this->context->almacenes();
        $codigosPermitidos = $almacenes->pluck('codigo')->map(fn ($codigo) => (string) $codigo);

        $validated = $request->validate([
            'almacen' => ['required', 'string', Rule::in($codigosPermitidos->all())],
            'q' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $almacenCodigo = (string) $validated['almacen'];
        $search = trim((string) ($validated['q'] ?? ''));
        $page = (int) ($validated['page'] ?? 1);

        $resultado = WmsIngreso::query()
            ->with('creador:id,name')
            ->where('almacen', $almacenCodigo)
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('codigo', 'like', "%{$search}%")
                        ->orWhere('descrip', 'like', "%{$search}%")
                        ->orWhere('descrip1', 'like', "%{$search}%")
                        ->orWhere('pallet', 'like', "%{$search}%")
                        ->orWhere('clote', 'like', "%{$search}%")
                        ->orWhere('galpon', 'like', "%{$search}%")
                        ->orWhere('ubicacion', 'like', "%{$search}%")
                        ->orWhere('cantidad', 'like', "%{$search}%")
                        ->orWhere('rdocum', 'like', "%{$search}%")
                        ->orWhere('doc_origen', 'like', "%{$search}%")
                        ->orWhereRaw("DATE_FORMAT(rfecha, '%d/%m/%Y') like ?", ["%{$search}%"])
                        ->orWhereHas('creador', function ($u) use ($search) {
                            $u->where('name', 'like', "%{$search}%");
                        })
                        ->orWhere('motivo', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15, ['*'], 'page', $page);

        $data = $resultado->through(function (WmsIngreso $ingreso) {
            return [
                'id' => $ingreso->id,
                'rdocum' => $ingreso->rdocum,
                'doc_origen' => $ingreso->doc_origen,
                'rfecha' => optional($ingreso->rfecha)->format('d/m/Y'),
                'tipo_ingreso' => $ingreso->tipo_ingreso,
                'motivo' => $ingreso->motivo,
                'pallet' => $ingreso->pallet,
                'codigo' => $ingreso->codigo,
                'clote' => $ingreso->clote,
                'descripcion' => trim("{$ingreso->descrip} {$ingreso->descrip1}"),
                'cantidad' => $ingreso->cantidad,
                'almacen' => $ingreso->almacen,
                'galpon' => $ingreso->galpon,
                'ubicacion' => $ingreso->ubicacion,
                'usuario' => $ingreso->creador->name ?? 'N/D',
                'creado' => optional($ingreso->created_at)->format('d/m/Y H:i'),
                'pdf_url' => $ingreso->doc_origen !== null && $ingreso->doc_origen !== ''
                    ? route('wms.ingresos.ver.pdf', [
                        'almacen' => $ingreso->almacen,
                        'docOrigen' => $ingreso->doc_origen,
                    ])
                    : null,
            ];
        });

        return response()->json([
            'data' => $data->items(),
            'current_page' => $resultado->currentPage(),
            'last_page' => $resultado->lastPage(),
            'total' => $resultado->total(),
        ]);
    }

    /**
     * Genera una nota PDF consolidada por documento de origen y almacén.
     * La pertenencia del usuario al almacén se vuelve a comprobar aquí.
     */
    public function pdf(string $almacen, string $docOrigen)
    {
        $codigosPermitidos = $this->context->almacenes()
            ->pluck('codigo')
            ->map(fn ($codigo) => (string) $codigo);

        abort_unless($codigosPermitidos->contains((string) $almacen), 403);

        $ingresos = WmsIngreso::query()
            ->with('creador:id,name')
            ->where('almacen', $almacen)
            ->where('doc_origen', $docOrigen)
            ->orderBy('codigo')
            ->orderBy('clote')
            ->orderBy('created_at')
            ->get();

        abort_if($ingresos->isEmpty(), 404, 'No existen ingresos para el documento solicitado.');

        $datos = [
            'almacen' => $almacenes = $this->context->almacenes()
                ->firstWhere('codigo', $almacen),
            'docOrigen' => $docOrigen,
            'tipoIngreso' => $ingresos->first()->tipo_ingreso,
            'ingresos' => $ingresos,
            'totalCantidad' => $ingresos->sum('cantidad'),
            'generado' => now()->format('d/m/Y H:i'),
        ];

        $pdf = \\Barryvdh\\DomPDF\\Facade\\Pdf::loadView('wms.ingresos.pdf', $datos)
            ->setPaper('letter', 'landscape');

        return $pdf->stream("nota-ingreso-{$docOrigen}-{$almacen}.pdf");
    }
}
