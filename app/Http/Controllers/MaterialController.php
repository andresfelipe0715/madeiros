<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MaterialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        Gate::authorize('view-materials');

        $query = Material::query();

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $materials = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('materials.index', compact('materials'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        Gate::authorize('create-materials');

        return view('materials.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create-materials');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:materials,name'],
            'stock_quantity' => ['required', 'numeric', 'min:0'],
        ]);

        Material::create($validated);

        return redirect()->route('materials.index')->with('success', 'Material creado exitosamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Material $material): View
    {
        Gate::authorize('edit-materials');

        return view('materials.edit', compact('material'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Material $material): RedirectResponse
    {
        Gate::authorize('edit-materials');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:materials,name,'.$material->id],
            'stock_quantity' => ['required', 'numeric', 'min:0'],
        ]);

        $material->update($validated);

        return redirect()->route('materials.index')->with('success', 'Material actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Material $material): RedirectResponse
    {
        Gate::authorize('edit-materials');

        if ($material->orderMaterials()->exists()) {
            return back()->with('error', 'No se puede eliminar el material porque está asociado a una o más órdenes.');
        }

        $material->delete();

        return redirect()->route('materials.index')->with('success', 'Material eliminado exitosamente.');
    }
}
