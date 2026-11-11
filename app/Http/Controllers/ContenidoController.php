<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContenidoRequest;
use App\Http\Requests\HorarioRequest;
use App\Models\Contenido;
use App\Models\Horario;
use App\Support\Imagenes;
use Illuminate\Support\Facades\DB;

class ContenidoController extends Controller
{
    public function index()
    {
        return view('admin.contenidos', [
            'horarios' => Horario::orderBy('dia')->get(),
            'contacto' => Contenido::valores(array_keys(Contenido::EDITABLES)),
            'qrPagoUrl' => Contenido::qrPagoUrl(),
        ]);
    }

    public function updateHorarios(HorarioRequest $request)
    {
        $dias = $request->validated()['dias'];

        DB::transaction(function () use ($dias) {
            foreach ($dias as $dia) {
                Horario::updateOrCreate(
                    ['dia' => $dia['dia']],
                    [
                        'apertura' => $dia['apertura'].':00',
                        'cierre' => $dia['cierre'].':00',
                        'activo' => (bool) ($dia['activo'] ?? false),
                    ]
                );
            }
        });

        return back()->with('success', 'Horarios actualizados.');
    }

    public function updateContacto(ContenidoRequest $request)
    {
        $valores = $request->validated();

        DB::transaction(function () use ($request, $valores) {
            if ($request->boolean('eliminar_qr_pago')) {
                $anterior = Contenido::valorDe('qr_pago');
                if ($anterior) {
                    Imagenes::eliminar($anterior);
                }
                Contenido::updateOrCreate(
                    ['clave' => 'qr_pago'],
                    ['valor' => '']
                );
            } elseif ($request->hasFile('qr_pago')) {
                $anterior = Contenido::valorDe('qr_pago');
                if ($anterior) {
                    Imagenes::eliminar($anterior);
                }
                $ruta = Imagenes::guardar($request->file('qr_pago'), 'contenidos');
                Contenido::updateOrCreate(
                    ['clave' => 'qr_pago'],
                    ['valor' => $ruta]
                );
            }

            foreach ($valores as $clave => $valor) {
                if (in_array($clave, ['qr_pago', 'eliminar_qr_pago'], true)) {
                    continue;
                }
                Contenido::updateOrCreate(
                    ['clave' => $clave],
                    ['valor' => $valor ?? '']
                );
            }
        });

        return back()->with('success', 'Datos de contacto actualizados.');
    }
}