<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContenidoRequest;
use App\Http\Requests\HorarioRequest;
use App\Models\Contenido;
use App\Models\Horario;
use Illuminate\Support\Facades\DB;

class ContenidoController extends Controller
{
    public function index()
    {
        return view('admin.contenidos', [
            'horarios' => Horario::orderBy('dia')->get(),
            'contacto' => Contenido::valores(array_keys(Contenido::EDITABLES)),
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

        DB::transaction(function () use ($valores) {
            foreach ($valores as $clave => $valor) {
                Contenido::updateOrCreate(
                    ['clave' => $clave],




                    ['valor' => $valor ?? '']
                );
            }
        });

        return back()->with('success', 'Datos de contacto actualizados.');
    }
}