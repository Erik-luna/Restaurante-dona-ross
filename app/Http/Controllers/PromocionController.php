<?php

namespace App\Http\Controllers;

use App\Http\Requests\PromocionRequest;
use App\Models\Promocion;
use Illuminate\Support\Facades\Storage;

class PromocionController extends Controller
{
    public function index()
    {
        $promociones = Promocion::latest()->paginate(10);

        return view('admin.promociones.index', compact('promociones'));
    }

    public function create()
    {
        return view('admin.promociones.create');
    }

    public function store(PromocionRequest $request)
    {
        $imagen = $request->hasFile('imagen')
            ? ['imagen' => $request->file('imagen')->store('promociones', 'public')]
            : [];

        Promocion::create($request->atributosPromocion($imagen));

        return redirect()->route('admin.promociones.index')->with('success', 'Promoción creada.');
    }

    public function edit(Promocion $promocione)
    {
        return view('admin.promociones.edit', ['promocion' => $promocione]);
    }

    public function update(PromocionRequest $request, Promocion $promocione)
    {
        $imagen = [];

        if ($request->hasFile('imagen')) {
            if ($promocione->imagen) {
                Storage::disk('public')->delete($promocione->imagen);
            }

            $imagen = ['imagen' => $request->file('imagen')->store('promociones', 'public')];
        }

        $promocione->update($request->atributosPromocion($imagen));

        return redirect()->route('admin.promociones.index')->with('success', 'Promoción actualizada.');
    }

    public function destroy(Promocion $promocione)
    {
        if ($promocione->imagen) {
            Storage::disk('public')->delete($promocione->imagen);
        }

        $promocione->delete();

        return redirect()->route('admin.promociones.index')->with('success', 'Promoción eliminada.');
    }
}
