@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="mb-1">Configuración laboral</h4>
            <p class="text-muted mb-0">Consulta y sincroniza la información laboral desde RRHH.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">LICENSE</label>
                    <input type="text" name="license" value="{{ request('license') }}" class="form-control" placeholder="Ej. 5287807">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="">Todos</option>
                        <option value="ACTIVO" @selected(request('estado') === 'ACTIVO')>ACTIVO</option>
                        <option value="INACTIVO" @selected(request('estado') === 'INACTIVO')>INACTIVO</option>
                    </select>
                </div>
                <div class="col-md-auto">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                    <a href="{{ route('nomina.configuraciones-laborales.index') }}" class="btn btn-outline-secondary">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>LICENSE</th>
                            <th>Trabajador</th>
                            <th>Área</th>
                            <th>Cargo</th>
                            <th>Agencia</th>
                            <th>Configuración</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($personas as $persona)
                            @php($config = $persona->configuracionesLaborales->first())
                            <tr>
                                <td><strong>{{ $persona->license }}</strong></td>
                                <td>{{ $persona->nombre_completo }}</td>
                                <td>{{ $config?->area_nombre ?? '—' }}</td>
                                <td>{{ $config?->cargo_nombre ?? '—' }}</td>
                                <td>{{ $config?->agencia_nombre ?? '—' }}</td>
                                <td>
                                    @if($config)
                                        <span class="badge bg-success">Registrada</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pendiente</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('nomina.configuraciones-laborales.show', $persona) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <form action="{{ route('nomina.configuraciones-laborales.sincronizar', $persona->license) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Sincronizar desde RRHH">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-4 text-muted">No se encontraron trabajadores.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($personas->hasPages())
            <div class="card-footer">{{ $personas->links() }}</div>
        @endif
    </div>
</div>
@endsection
