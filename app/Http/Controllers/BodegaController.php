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
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%");
            });
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
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $previousQuantity = $material->bodega_quantity;
        $newQuantity = (float) $validated['bodega_quantity'];

        if ($previousQuantity != $newQuantity) {
            $material->update([
                'bodega_quantity' => $newQuantity,
            ]);

            \App\Models\InventoryLog::create([
                'material_id' => $material->id,
                'user_id' => auth()->id(),
                'action' => 'bodega_adjustment',
                'previous_stock_quantity' => $previousQuantity, // Using this for bodega quantity in this context
                'new_stock_quantity' => $newQuantity,
                'notes' => $validated['notes'] ?? 'Ajuste manual de bodega',
            ]);
        }

        return redirect()->route('bodega.index')->with('success', 'Cantidad en bodega actualizada exitosamente.');
    }

    /**
     * Show form to register a new entry of material to bodega.
     */
    public function entry(Material $material): View
    {
        Gate::authorize('edit-bodega');

        return view('bodega.entry', compact('material'));
    }

    /**
     * Store a new entry of material to bodega.
     */
    public function storeEntry(Request $request, Material $material): RedirectResponse
    {
        Gate::authorize('edit-bodega');

        $validated = $request->validate([
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['required', 'string', 'max:255'], // e.g. "Factura #123"
        ]);

        $quantity = (float) $validated['quantity'];
        $previousQuantity = $material->bodega_quantity;

        $material->increment('bodega_quantity', $quantity);

        \App\Models\InventoryLog::create([
            'material_id' => $material->id,
            'user_id' => auth()->id(),
            'action' => 'bodega_entry',
            'previous_stock_quantity' => $previousQuantity,
            'new_stock_quantity' => $previousQuantity + $quantity,
            'notes' => $validated['notes'],
        ]);

        return redirect()->route('bodega.index')->with('success', 'Ingreso a bodega registrado exitosamente.');
    }

    /**
     * Display a listing of inventory logs for bodega.
     */
    public function logs(Request $request, ?Material $material = null): View
    {
        Gate::authorize('view-bodega');

        $query = \App\Models\InventoryLog::with(['material', 'user'])
            ->whereIn('action', ['transfer', 'bodega_entry', 'bodega_adjustment'])
            ->latest();

        if ($material && $material->exists) {
            $query->where('material_id', $material->id);
        }

        $logs = $query->paginate(20)->withQueryString();

        return view('bodega.logs', compact('logs', 'material'));
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

            $previousStock = $material->stock_quantity;

            $material->decrement('bodega_quantity', $quantity);
            $material->increment('stock_quantity', $quantity);

            \App\Models\InventoryLog::create([
                'material_id' => $material->id,
                'user_id' => auth()->id(),
                'action' => 'transfer',
                'previous_stock_quantity' => $previousStock,
                'new_stock_quantity' => $previousStock + $quantity,
                'notes' => 'Transferencia desde bodega',
            ]);
        });

        return back()->with('success', 'Transferencia realizada exitosamente.');
    }
}
