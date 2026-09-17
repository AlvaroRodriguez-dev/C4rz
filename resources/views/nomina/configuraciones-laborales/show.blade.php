@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="mb-1">Configuración laboral</h4>
            <p class="text-muted mb-0">{{ $personal->nombre_completo }} · LICENSE {{ $personal->license }}</p>
        </div>
        <a href="{{ route('nomina.configuraciones-laborales.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><strong>Trabajador</strong></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">LICENSE</dt><dd class="col-sm-7">{{ $personal->license }}</dd>
                        <dt class="col-sm-5">Nombre</dt><dd class="col-sm-7">{{ $personal->nombre }}</dd>
                        <dt class="col-sm-5">Apellido</dt><dd class="col-sm-7">{{ $personal->apellido }}</dd>
                        <dt class="col-sm-5">Estado</dt><dd class="col-sm-7">{{ $personal->estado }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><strong>Historial laboral local</strong></div>
                <div class="card-body p-0">
                    @forelse($personal->configuracionesLaborales as $config)
                        <div class="p-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <strong>Vigencia</strong>
                                <span class="badge bg-info text-dark">
                                    {{ optional($config->fecha_inicio)->format('d/m/Y') }}
                                    —
                                    {{ optional($config->fecha_fin)->format('d/m/Y') ?? 'Actual' }}
                                </span>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6"><small class="text-muted d-block">Área</small>{{ $config->area_nombre ?? '—' }} @if($config->area_codigo) ({{ $config->area_codigo }}) @endif</div>
                                <div class="col-md-6"><small class="text-muted d-block">Sección</small>{{ $config->seccion_nombre ?? '—' }}</div>
                                <div class="col-md-6"><small class="text-muted d-block">Cargo</small>{{ $config->cargo_nombre ?? '—' }}</div>
                                <div class="col-md-6"><small class="text-muted d-block">Jerarquía</small>{{ $config->jerarquia_nombre ?? '—' }}</div>
                                <div class="col-md-6"><small class="text-muted d-block">Agencia</small>{{ $config->agencia_nombre ?? '—' }} @if($config->agencia_codigo) ({{ $config->agencia_codigo }}) @endif</div>
                                <div class="col-md-6"><small class="text-muted d-block">Ciudad</small>{{ $config->ciudad ?? '—' }}</div>
                                <div class="col-md-6"><small class="text-muted d-block">Fecha ingreso</small>{{ optional($config->fecha_ingreso)->format('d/m/Y') ?? '—' }}</div>
                                <div class="col-md-6"><small class="text-muted d-block">Fecha retiro</small>{{ optional($config->fecha_retiro)->format('d/m/Y') ?? '—' }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 text-muted">No existe una configuración laboral local.</div>
                    @endforelse
                </div>
                <div class="card-footer">
                    <form action="{{ route('nomina.configuraciones-laborales.sincronizar', $personal->license) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-sync-alt"></i> Sincronizar desde RRHH
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
