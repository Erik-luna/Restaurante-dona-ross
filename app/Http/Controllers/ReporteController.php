<?php

namespace App\Http\Controllers;

use App\Services\ReporteService;

/**
 * Reportes del administrador.
 *
 * El controlador no calcula nada: pide los bloques al ReporteService y los pasa a la
 * vista. Si las cifras se armaran aquí, el mismo número habría que repetirlo en cada
 * gráfico y en cada tarjeta, y tarde o temprano dos de ellos dejarían de coincidir.
 */
class ReporteController extends Controller
{
    public function __construct(private ReporteService $reportes)
    {
    }

    public function index()
    {
        $porDia = $this->reportes->ventasPorDia();
        $porMes = $this->reportes->ventasPorMes();
        $totales = $this->reportes->totales();
        $cajas = $this->reportes->flujoDeCaja();
        $platos = $this->reportes->platosMasVendidos();

        return view('admin.reportes', compact('porDia', 'porMes', 'totales', 'cajas', 'platos'));
    }
}