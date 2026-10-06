<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContenidoRequest;
use App\Http\Requests\HorarioRequest;
use App\Models\Contenido;
use App\Models\Horario;
use Illuminate\Support\Facades\DB;

/**
 * Pantalla de contenido del sitio: lo que el administrador cambia sin tocar código.
 *
 * Son dos formularios y no uno porque los datos son de dos formas distintas: los
 * horarios son siete filas con una estructura, y los datos de contacto son pares de
 * clave y valor. Meterlos en la misma tabla obligaría a guardar "08:00 - 20:00" como
 * texto suelto y el sistema no podría responder si el local está abierto ahora.
 */
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

        // Guardar los siete días es una operación o no es nada: si fallara a la
        // mitad, el restaurante se quedaría con la mitad del horario viejo y la otra
        // nueva, sin que nadie lo haya pedido.
        DB::transaction(function () use ($dias) {
            foreach ($dias as $dia) {
                Horario::updateOrCreate(
                    ['dia' => $dia['dia']],
                    [
                        // Se normaliza a HH:MM:SS. MySQL devuelve la columna TIME con
                        // segundos y SQLite guarda lo que se le mande; sin esta
                        // comparación `estaAbierto()` leería "08:00" contra "08:00:00"
                        // y el local aparecería cerrado justo a la hora de apertura.
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
                    // Un campo vacío se guarda como cadena vacía y no como null: la
                    // diferencia importa porque `valorDe()` respeta el vacío (alguien
                    // lo borró a propósito) pero rellena con el defecto una fila que
                    // no existe.
                    ['valor' => $valor ?? '']
                );
            }
        });

        return back()->with('success', 'Datos de contacto actualizados.');
    }
}