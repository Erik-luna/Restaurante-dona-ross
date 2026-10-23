@extends('layouts.admin')
@section('panel-title', 'Administración')
@section('sidebar')@include('partials.sidebar-admin')@endsection
@section('title', 'Contenido del Sitio')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-1 font-serif" style="color: var(--ross-primary);">Contenido del Sitio</h2>
        <p class="text-muted small mb-0">
            Horarios y datos de contacto. Lo que guardes aquí cambia la portada, el catálogo
            y el pie de página, sin tocar código.
        </p>
    </div>
</div>




<div class="row g-4">
    <div class="col-xl-7">
        <div class="card-ross p-4">
            <h5 class="fw-bold mb-1 font-serif" style="color: var(--ross-primary);">
                <i class="bi bi-clock-history text-danger me-2"></i> Horarios de atención
            </h5>
            <p class="text-muted small mb-4">
                El indicador "Abierto ahora" de la portada se calcula con estos horarios.
                Un día marcado como cerrado no se muestra en el pie de página.
            </p>

            <form action="{{ route('admin.contenidos.horarios') }}" method="POST">
                @csrf

                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th style="width: 40%;">Día</th>
                                <th>Apertura</th>
                                <th>Cierre</th>
                                <th class="text-center">Abierto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($horarios as $horario)
                                <tr>
                                    <td class="fw-semibold">{{ $horario->nombreDia() }}</td>
                                    <td>
                                        <input type="time" name="dias[{{ $horario->dia }}][apertura]"
                                               value="{{ old('dias.'.$horario->dia.'.apertura', substr($horario->apertura, 0, 5)) }}"
                                               class="form-control form-control-sm @error('dias.'.$horario->dia.'.apertura') is-invalid @enderror"
                                               required>
                                    </td>
                                    <td>
                                        <input type="time" name="dias[{{ $horario->dia }}][cierre]"
                                               value="{{ old('dias.'.$horario->dia.'.cierre', substr($horario->cierre, 0, 5)) }}"
                                               class="form-control form-control-sm @error('dias.'.$horario->dia.'.cierre') is-invalid @enderror"
                                               required>
                                    </td>
                                    <td class="text-center">



                                        <input type="hidden" name="dias[{{ $horario->dia }}][activo]" value="0">
                                        <input class="form-check-input" type="checkbox"
                                               name="dias[{{ $horario->dia }}][activo]" value="1"
                                               id="dia{{ $horario->dia }}"
                                               {{ old('dias.'.$horario->dia.'.activo', $horario->activo) ? 'checked' : '' }}>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="btn btn-ross-primary">
                        <i class="bi bi-save me-1"></i> Guardar horarios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card-ross p-4 mb-4">
            <h5 class="fw-bold mb-1 font-serif" style="color: var(--ross-primary);">
                <i class="bi bi-telephone text-danger me-2"></i> Datos de contacto
            </h5>
            <p class="text-muted small mb-4">
                Se puede escribir como "+591 70000000" o "+591 (2) 700-0000": el enlace del
                sitio se arma solo con los dígitos.
            </p>

            <form action="{{ route('admin.contenidos.contacto') }}" method="POST">
                @csrf

                @foreach(['telefono' => 'Teléfono', 'whatsapp' => 'WhatsApp', 'direccion' => 'Dirección', 'facebook' => 'Facebook (URL)', 'instagram' => 'Instagram (URL)', 'correo' => 'Correo electrónico'] as $clave => $etiqueta)
                    <div class="mb-3">
                        <label class="form-label small fw-semibold" for="contacto_{{ $clave }}">{{ $etiqueta }}</label>
                        <input type="text" id="contacto_{{ $clave }}" name="{{ $clave }}"
                               value="{{ old($clave, $contacto[$clave]) }}"
                               class="form-control form-control-sm @error($clave) is-invalid @enderror"
                               @if($clave === 'facebook' || $clave === 'instagram') placeholder="https://" @endif>
                        @error($clave)
                            <div class="form-text text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                @endforeach

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-ross-primary">
                        <i class="bi bi-save me-1"></i> Guardar contacto
                    </button>
                </div>
            </form>
        </div>



        <div class="card-ross p-4">
            <h6 class="fw-bold mb-3" style="color: var(--ross-primary);">
                <i class="bi bi-eye text-danger me-2"></i> Así se verá en el pie
            </h6>
            <ul class="list-unstyled small mb-0">
                <li class="mb-2">
                    <i class="bi bi-clock-fill text-secondary me-2"></i>
                    @forelse(\App\Models\Horario::agrupados() as $grupo)
                        @if(! $grupo['activo'])
                            @continue
                        @endif
                        <div class="d-flex justify-content-between">
                            <span>{{ $grupo['etiqueta'] }}:</span>
                            <strong>{{ $grupo['rango'] }}</strong>
                        </div>
                    @empty
                        <span class="text-muted">Todavía no hay horarios configurados.</span>
                    @endforelse
                </li>
                <li class="mb-0">
                    <i class="bi bi-telephone-fill text-secondary me-2"></i>
                    {{ $contacto['telefono'] ?: 'Sin teléfono configurado.' }}
                </li>
            </ul>
        </div>
    </div>
</div>
@endsection