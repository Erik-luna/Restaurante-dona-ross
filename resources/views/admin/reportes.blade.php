@extends('layouts.admin')
@section('panel-title', 'Reportes')
@section('sidebar')@include('partials.sidebar-admin')@endsection
@section('title', 'Reportes')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-1 font-serif" style="color: var(--ross-primary);">Reportes</h2>
        <p class="text-muted small mb-0">
            Ventas de los últimos {{ count($porDia) }} días y de los últimos {{ count($porMes) }} meses.
            El delivery se cuenta por la fecha en que se cobró, no por la del pedido.
        </p>
    </div>
    <span class="badge bg-secondary-subtle text-secondary border px-3 py-2">
        <i class="bi bi-calendar3 me-1"></i> Actualizado al {{ now()->format('d/m/Y H:i') }}
    </span>
</div>




<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card-ross p-3 h-100">
            <small class="text-muted text-uppercase" style="letter-spacing:.05em;">Ventas de salón</small>
            <div class="fs-4 fw-bold font-serif" style="color: var(--ross-primary);">
                Bs. {{ number_format($totales['salon'], 2) }}
            </div>
            <small class="text-muted">Cobradas en el mostrador</small>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card-ross p-3 h-100">
            <small class="text-muted text-uppercase" style="letter-spacing:.05em;">Cobros delivery</small>
            <div class="fs-4 fw-bold font-serif text-success">
                Bs. {{ number_format($totales['delivery'], 2) }}
            </div>
            <small class="text-muted">Por la fecha de cobro</small>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card-ross p-3 h-100">
            <small class="text-muted text-uppercase" style="letter-spacing:.05em;">Ingresos totales</small>
            <div class="fs-4 fw-bold font-serif">
                Bs. {{ number_format($totales['total'], 2) }}
            </div>
            <small class="text-muted">{{ $totales['operaciones'] }} operaciones cobradas</small>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card-ross p-3 h-100">
            <small class="text-muted text-uppercase" style="letter-spacing:.05em;">Cajas cerradas</small>
            <div class="fs-4 fw-bold font-serif">{{ $cajas->count() }}</div>
            <small class="text-muted">
                @php $descuadre = $cajas->sum(fn ($c) => (float) $c->diferencia); @endphp
                @if(abs($descuadre) < 0.01)
                    <i class="bi bi-check-circle text-success"></i> Todo cuadra
                @else
                    Diferencia acumulada: Bs. {{ number_format($descuadre, 2) }}
                @endif
            </small>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">


    <div class="col-xl-8">
        <div class="card-ross p-4 h-100">
            <h5 class="fw-bold mb-3 font-serif" style="color: var(--ross-primary);">
                <i class="bi bi-bar-chart-line text-danger me-2"></i> Ventas por día
            </h5>
            <div style="height: 280px;">
                <canvas id="graficoDiario"></canvas>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card-ross p-4 h-100">
            <h5 class="fw-bold mb-3 font-serif" style="color: var(--ross-primary);">
                <i class="bi bi-calendar3 text-danger me-2"></i> Ventas por mes
            </h5>
            @if(count($porMes) === 0)
                <p class="text-muted small mb-0">Todavía no hay ventas con las que armar el histórico mensual.</p>
            @else
                <div style="height: 280px;">
                    <canvas id="graficoMensual"></canvas>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-7">
        <div class="card-ross p-0 h-100">
            <div class="p-4 pb-0">
                <h5 class="fw-bold mb-1 font-serif" style="color: var(--ross-primary);">
                    <i class="bi bi-safe text-danger me-2"></i> Flujo de caja
                </h5>
                <p class="text-muted small mb-3">
                    Cajas cerradas con el dinero contado contra el esperado. Las cifras están
                    congeladas al cerrar el turno: un cobro posterior no puede reescribirlas.
                </p>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Cajero</th>
                            <th class="text-end">Esperado</th>
                            <th class="text-end">Contado</th>
                            <th class="text-end">Diferencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cajas as $caja)
                            @php $diferencia = (float) $caja->diferencia; @endphp
                            <tr>
                                <td>{{ $caja->fecha->format('d/m/Y') }}</td>
                                <td>{{ $caja->user?->name ?? 'Eliminado' }}</td>
                                <td class="text-end">Bs. {{ number_format((float) $caja->total_esperado, 2) }}</td>
                                <td class="text-end">Bs. {{ number_format((float) $caja->total_declarado, 2) }}</td>
                                <td class="text-end">
                                    @if(round($diferencia, 2) === 0.0)
                                        <span class="badge bg-success-subtle text-success border border-success">Cuadra</span>
                                    @elseif($diferencia > 0)
                                        <span class="badge bg-success-subtle text-success border border-success">+Bs. {{ number_format($diferencia, 2) }}</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger">Bs. {{ number_format($diferencia, 2) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    Todavía no se ha cerrado ninguna caja.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card-ross p-0 h-100">
            <div class="p-4 pb-0">
                <h5 class="fw-bold mb-1 font-serif" style="color: var(--ross-primary);">
                    <i class="bi bi-trophy text-danger me-2"></i> Platos más vendidos
                </h5>
                <p class="text-muted small mb-3">
                    Acumulado de toda la operación, salón y delivery juntos, con el precio
                    con el que se vendió cada plato.
                </p>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 45px;">#</th>
                            <th>Plato</th>
                            <th class="text-end">Unidades</th>
                            <th class="text-end">Importe</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($platos as $indice => $fila)
                            <tr>
                                <td class="fw-bold text-muted">{{ $indice + 1 }}</td>
                                <td>{{ $fila['producto']->nombre }}</td>
                                <td class="text-end">{{ $fila['unidades'] }}</td>
                                <td class="text-end">Bs. {{ number_format($fila['importe'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    Todavía no se ha vendido nada.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    @php
        $json = fn ($valor) => json_encode($valor, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $mesesCortos = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $etiquetaMes = fn (string $mes) => $mesesCortos[(int) substr($mes, 5, 2) - 1].' '.substr($mes, 0, 4);
    @endphp

    const primario = getComputedStyle(document.documentElement).getPropertyValue('--ross-primary').trim() || '#8B1E3F';
    const secundario = '#28a745';
    if (typeof Chart === 'undefined') {
        document.querySelectorAll('canvas').forEach((lienzo) => {
            lienzo.closest('.card-ross').querySelector('h5').insertAdjacentHTML(
                'afterend',
                '<p class="text-muted small mb-0">No se pudieron dibujar los gráficos: falta conexión a la red para cargar la librería.</p>'
            );
        });
    } else {
        Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
        Chart.defaults.color = '#6c757d';
        const opcionesComunes = {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: (valor) => 'Bs. ' + Number(valor).toLocaleString('es-BO') },
                    grid: { color: 'rgba(0,0,0,.06)' }
                }
            },
            plugins: {
                legend: { position: 'bottom' },
                tooltip: { callbacks: { label: (ctx) => ctx.dataset.label + ': Bs. ' + Number(ctx.parsed.y).toLocaleString('es-BO') } }
            }
        };

        new Chart(document.getElementById('graficoDiario'), {
            type: 'bar',
            data: {
                labels: {!! $json(collect($porDia)->pluck('dia')->map(fn ($d) => \Carbon\Carbon::parse($d)->format('d/m'))->all()) !!},
                datasets: [
                    {
                        label: 'Salón',
                        data: {!! $json(collect($porDia)->pluck('salon')->map(fn ($v) => (float) $v)->all()) !!},
                        backgroundColor: primario,
                        borderRadius: 4
                    },
                    {
                        label: 'Delivery',
                        data: {!! $json(collect($porDia)->pluck('delivery')->map(fn ($v) => (float) $v)->all()) !!},
                        backgroundColor: secundario,
                        borderRadius: 4
                    }
                ]
            },
            options: { ...opcionesComunes }
        });

        @if(count($porMes) > 0)
            new Chart(document.getElementById('graficoMensual'), {
                type: 'line',
                data: {
                    labels: {!! $json(collect($porMes)->map(fn ($fila) => $etiquetaMes($fila['mes']))->all()) !!},
                    datasets: [
                        {
                            label: 'Salón',
                            data: {!! $json(collect($porMes)->pluck('salon')->map(fn ($v) => (float) $v)->all()) !!},
                            borderColor: primario,
                            backgroundColor: 'rgba(139,30,63,.08)',
                            fill: true,
                            tension: .25,
                            pointRadius: 3
                        },
                        {
                            label: 'Delivery',
                            data: {!! $json(collect($porMes)->pluck('delivery')->map(fn ($v) => (float) $v)->all()) !!},
                            borderColor: secundario,
                            backgroundColor: 'rgba(40,167,69,.08)',
                            fill: true,
                            tension: .25,
                            pointRadius: 3
                        }
                    ]
                },
                options: { ...opcionesComunes }
            });
        @endif
    }
</script>
@endpush