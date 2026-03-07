<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class BodegaController extends Controller
{
    /**
     * Display a listing of materials focusing on Bodega stock.
     */
    public function index(Request $request): View
    {
        Gate::authorize('view-bodega');

        $query = Material::query();

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $materials = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('bodega.index', compact('materials'));
    }

    /**
     * Show the form for editing bodega stock of the specified material.
     */
    public function edit(Material $material): View
    {
        Gate::authorize('edit-bodega');

        return view('bodega.edit', compact('material'));
    }

    /**
     * Update the bodega stock of the specified material in storage.
     */
    public function update(Request $request, Material $material): RedirectResponse
    {
        Gate::authorize('edit-bodega');

        $validated = $request->validate([
            'bodega_quantity' => ['required', 'numeric', 'min:0'],
        ]);

        $material->update([
            'bodega_quantity' => $validated['bodega_quantity'],
        ]);

        return redirect()->route('bodega.index')->with('success', 'Cantidad en bodega actualizada exitosamente.');
    }

    /**
     * Transfer quantity from bodega to factory stock.
     */
    public function transfer(Request $request, Material $material): RedirectResponse
    {
        Gate::authorize('edit-bodega');

        $validated = $request->validate([
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:'.$material->bodega_quantity],
        ]);

        $quantity = (float) $validated['quantity'];

        \Illuminate\Support\Facades\DB::transaction(function () use ($material, $quantity) {
            if (app()->environment() !== 'testing') {
                $material->lockForUpdate();
            }
            $material->refresh();

            if ($material->bodega_quantity < $quantity) {
                throw new \Exception('No hay suficiente cantidad en bodega.');
            }

            $material->decrement('bodega_quantity', $quantity);
            $material->increment('stock_quantity', $quantity);
        });

        return back()->with('success', 'Transferencia realizada exitosamente.');
    }
}
