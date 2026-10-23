<?php

namespace App\Http\Controllers;
use App\Services\ReporteService;
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