@extends('layouts.admin')
@section('panel-title', 'Administración')
@section('sidebar')@include('partials.sidebar-admin')@endsection
@section('title', 'Editar Producto')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="fw-bold mb-1 font-serif" style="color: var(--ross-primary);">Editar: {{ $producto->nombre }}</h2>
        <p class="text-muted small mb-0">Modifica los detalles y el precio del platillo seleccionado.</p>
    </div>
    <a href="{{ route('admin.productos.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
        <i class="bi bi-arrow-left me-1"></i> Volver a la lista
    </a>
</div>

<div class="card-ross p-4 p-md-5">
    <form action="{{ route('admin.productos.update', $producto) }}" method="POST" enctype="multipart/form-data">
        @csrf @method('PUT')
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nombre del Platillo *</label>
                <input type="text" name="nombre" class="form-control" value="{{ old('nombre', $producto->nombre) }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Categoría</label>
                <select name="categoria_id" class="form-select">
                    <option value="">Sin categoría</option>
                    @foreach($categorias as $c)
                        <option value="{{ $c->id }}" {{ old('categoria_id', $producto->categoria_id) == $c->id ? 'selected' : '' }}>{{ $c->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-12">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-control" rows="3">{{ old('descripcion', $producto->descripcion) }}</textarea>
            </div>

            <div class="col-md-4">
                <label class="form-label">Precio Unitario (Bs.) *</label>
                <div class="input-group">
                    <span class="input-group-text bg-white">Bs.</span>
                    <input type="number" step="0.01" name="precio" class="form-control" value="{{ old('precio', $producto->precio) }}" required>
                </div>
            </div>

            <div class="col-md-4">
                <label class="form-label">Stock de hoy</label>
                @php $d = $jornada?->disponible() ?? 0; @endphp
                <div class="form-control bg-light fw-bold {{ $d > 0 ? 'text-success' : 'text-danger' }}">
                    {{ $d }} disponible{{ $d === 1 ? '' : 's' }}
                </div>
                <small class="text-muted">
                    El stock pertenece a la jornada, no al producto, y no se cambia desde aquí:
                    corregirlo aquí dejaría la carga menor que lo ya vendido.
                    <a href="{{ route(auth()->user()->panel().'.stock.index') }}">Ir a Control de Stock</a>.
                </small>
            </div>

            <div class="col-md-4">
                <label class="form-label">Imagen</label>
                <input type="file" name="imagen" class="form-control" accept="image/*">
                @if($producto->imagen)
                    <div class="d-flex align-items-center gap-2 mt-2">
                        <img src="{{ $producto->imagen_url ?: asset('img/platos/plato1.jpeg') }}"
                             onerror="this.onerror=null;this.src='{{ asset('img/platos/plato1.jpeg') }}';"
                             class="rounded shadow-sm" width="60" height="60" style="object-fit: cover;">
                        <small class="text-muted">Imagen actual</small>
                    </div>
                @endif
            </div>

            <div class="col-md-6">
                <div class="p-3 bg-light rounded-3 border">
                    <div class="form-check form-switch mb-0">
                        <input type="checkbox" name="destacado" value="1" class="form-check-input" id="destacado" {{ old('destacado', $producto->destacado) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="destacado">Plato Destacado (Aparece en inicio)</label>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="p-3 bg-light rounded-3 border">
                    <div class="form-check form-switch mb-0">
                        <input type="checkbox" name="activo" value="1" class="form-check-input" id="activo" {{ old('activo', $producto->activo) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="activo">Platillo Activo (Visible en tienda)</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 pt-4 border-top mt-4">
            <a href="{{ route('admin.productos.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Cancelar</a>
            <button type="submit" class="btn btn-ross-primary px-4">
                <i class="bi bi-check-lg me-1"></i> Actualizar Producto
            </button>
        </div>
    </form>
</div>
@endsection
